<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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

    public function __construct()
    {
        $this->batchId = Str::uuid()->toString();
    }

    /**
     * Import CSV file and handle duplicates
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
                'errors_details' => []
            ];

            DB::transaction(function () use ($records, &$results) {
                $duplicateGroups = [];
                $rowNumber = 1;

                foreach ($records as $record) {
                    $rowNumber++;
                    
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

                $results['duplicate_groups'] = $duplicateGroups;
            });

            return $this->buildResponse(true, 'Import completed successfully', $results);

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
     * Validate individual row data
     */
    private function validateRow(array $record, int $rowNumber): array
    {
        $validator = Validator::make($record, [
            'company_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => [
                    'row' => $rowNumber,
                    'data' => $record,
                    'validation_errors' => $validator->errors()->toArray()
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
}
