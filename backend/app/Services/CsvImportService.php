<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use League\Csv\Reader;
use League\Csv\Statement;

class CsvImportService
{
    private $errors = [];
    private $duplicates = [];
    private $imported = [];
    private $batchId;
    private $batchSize = 1000;
    private $maxErrors = 100;

    public function __construct()
    {
        $this->batchId = Str::uuid()->toString();
    }

    /**
     * Import CSV file and handle duplicates with batch processing
     */
    public function importCsv(UploadedFile $file): array
    {
        try {
            // Read CSV file
            $csv = Reader::createFromPath($file->getPathname(), 'r');
            $csv->setHeaderOffset(0);
            
            $records = Statement::create()->process($csv);
            $headers = $csv->getHeader();
            
            // Validate headers
            if (!$this->validateHeaders($headers)) {
                return $this->buildResponse(false, 'Invalid CSV headers. Expected: company_name, email, phone_number');
            }

            $results = [
                'imported' => 0,
                'duplicates' => 0,
                'errors' => 0,
                'duplicate_groups' => [],
                'errors_details' => [],
                'total_rows' => 0,
                'processed_rows' => 0
            ];

            // Convert records to array for batch processing
            $recordsArray = iterator_to_array($records);
            $results['total_rows'] = count($recordsArray);

            // Process records in batches
            $batches = array_chunk($recordsArray, $this->batchSize);
            $duplicateGroups = [];

            foreach ($batches as $batchIndex => $batch) {
                Log::info('Processing batch: ' . $batchIndex);
                Log::info('Batch size: ' . count($batch));
                Log::info('Batch: ' . json_encode($batch));
                try {
                    DB::transaction(function () use ($batch, &$results, &$duplicateGroups, $batchIndex) {
                        foreach ($batch as $recordIndex => $record) {
                            $rowNumber = ($batchIndex * $this->batchSize) + $recordIndex + 2; // +2 because CSV has header and 0-indexed
                            $results['processed_rows']++;
                            
                            // Stop processing if we hit too many errors
                            if ($results['errors'] >= $this->maxErrors) {
                                $results['errors_details'][] = [
                                    'row' => $rowNumber,
                                    'error' => 'Processing stopped due to too many errors (max: ' . $this->maxErrors . ')',
                                    'type' => 'processing_limit'
                                ];
                                return;
                            }
                            
                            // Validate row data
                            $validation = $this->validateRow($record, $rowNumber);
                            if (!$validation['valid']) {
                                $results['errors']++;
                                $results['errors_details'][] = $validation['errors'];
                                continue;
                            }

                            // Check for duplicates
                            $duplicateCheck = $this->checkForDuplicates($record);
                            
                            if ($duplicateCheck['is_duplicate']) {
                                $results['duplicates']++;
                                $duplicateGroupId = $duplicateCheck['group_id'];
                                
                                // Add to duplicate group
                                if (!isset($duplicateGroups[$duplicateGroupId])) {
                                    $duplicateGroups[$duplicateGroupId] = [];
                                }
                                $duplicateGroups[$duplicateGroupId][] = $record;
                                
                                // Create duplicate record
                                $this->createClientRecord($record, true, $duplicateGroupId, $rowNumber);
                            } else {
                                $results['imported']++;
                                $this->createClientRecord($record, false, null, $rowNumber);
                            }
                        }
                    });
                } catch (\Exception $e) {
                    // Log batch error but continue with next batch
                    Log::error('Batch processing error: ' . $e->getMessage());
                    $results['errors']++;
                    $results['errors_details'][] = [
                        'row' => 'Batch ' . ($batchIndex + 1),
                        'error' => 'Batch processing error: ' . $e->getMessage(),
                        'type' => 'batch_error'
                    ];
                }
            }

            $results['duplicate_groups'] = $duplicateGroups;

            $message = 'Import completed successfully';
            if ($results['errors'] > 0) {
                $message .= " with {$results['errors']} errors";
            }
            if ($results['errors'] >= $this->maxErrors) {
                $message .= '. Processing was stopped due to too many errors.';
            }

            return $this->buildResponse(true, $message, $results);

        } catch (\Exception $e) {
            return $this->buildResponse(false, 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate CSV headers
     */
    private function validateHeaders(array $headers): bool
    {
        $requiredHeaders = ['company_name', 'email', 'phone_number'];
        return empty(array_diff($requiredHeaders, $headers));
    }

    /**
     * Validate individual row data with enhanced error messages
     */
    private function validateRow(array $record, int $rowNumber): array
    {
        $validator = Validator::make($record, [
            'company_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:255',
        ], [
            'company_name.required' => 'Company name is required',
            'company_name.string' => 'Company name must be a valid text',
            'company_name.max' => 'Company name cannot exceed 255 characters',
            'email.required' => 'Email address is required',
            'email.email' => 'Email must be a valid email address',
            'email.max' => 'Email cannot exceed 255 characters',
            'phone_number.required' => 'Phone number is required',
            'phone_number.string' => 'Phone number must be a valid text',
            'phone_number.max' => 'Phone number cannot exceed 255 characters',
        ]);

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->all() as $error) {
                $errors[] = $error;
            }

            return [
                'valid' => false,
                'errors' => [
                    'row' => $rowNumber,
                    'data' => $record,
                    'validation_errors' => $validator->errors()->toArray(),
                    'error_messages' => $errors,
                    'type' => 'validation_error'
                ]
            ];
        }

        return ['valid' => true];
    }

    /**
     * Check if record is a duplicate
     */
    private function checkForDuplicates(array $record): array
    {
        // Check if there's already a duplicate group for this exact data
        $existingDuplicateGroup = Client::where('company_name', $record['company_name'])
            ->where('email', $record['email'])
            ->where('phone_number', $record['phone_number'])
            ->whereNotNull('duplicate_group_id')
            ->first();

        if ($existingDuplicateGroup) {
            return [
                'is_duplicate' => true,
                'group_id' => $existingDuplicateGroup->duplicate_group_id
            ];
        }

        // Check if there's any existing client with this data
        $existingClient = Client::where('company_name', $record['company_name'])
            ->where('email', $record['email'])
            ->where('phone_number', $record['phone_number'])
            ->first();

        if ($existingClient) {
            // This is a duplicate of an existing record, but don't modify the existing record
            // Create a new group ID for this duplicate
            $newGroupId = Str::uuid()->toString();

            return [
                'is_duplicate' => true,
                'group_id' => $newGroupId
            ];
        }

        return ['is_duplicate' => false];
    }

    /**
     * Create client record in database
     */
    private function createClientRecord(array $record, bool $isDuplicate, ?string $duplicateGroupId, int $rowNumber): void
    {
        Client::create([
            'company_name' => $record['company_name'],
            'email' => $record['email'],
            'phone_number' => $record['phone_number'],
            'is_duplicate' => $isDuplicate,
            'duplicate_group_id' => $duplicateGroupId,
            'import_metadata' => [
                'batch_id' => $this->batchId,
                'row_number' => $rowNumber,
                'imported_at' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Build response array
     */
    private function buildResponse(bool $success, string $message, array $data = []): array
    {
        return [
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'batch_id' => $this->batchId
        ];
    }

    /**
     * Get import statistics
     */
    public function getImportStats(): array
    {
        return [
            'total_clients' => Client::count(),
            'unique_clients' => Client::unique()->count(),
            'duplicate_clients' => Client::duplicates()->count(),
            'duplicate_groups' => Client::duplicates()->distinct('duplicate_group_id')->count()
        ];
    }

    /**
     * Get batch processing configuration
     */
    public function getBatchConfig(): array
    {
        return [
            'batch_size' => $this->batchSize,
            'max_errors' => $this->maxErrors,
            'batch_id' => $this->batchId
        ];
    }

    /**
     * Set batch size for processing
     */
    public function setBatchSize(int $size): void
    {
        $this->batchSize = max(100, min(5000, $size)); // Between 100 and 5000
    }

    /**
     * Set maximum errors before stopping
     */
    public function setMaxErrors(int $maxErrors): void
    {
        $this->maxErrors = max(10, min(1000, $maxErrors)); // Between 10 and 1000
    }
}
