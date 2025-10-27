<?php

namespace App\Http\Controllers;

use App\Http\Requests\CsvImportRequest;
use App\Http\Resources\BatchConfigResource;
use App\Http\Resources\ClientCollection;
use App\Http\Resources\ClientResource;
use App\Http\Resources\ClientWithDuplicatesResource;
use App\Http\Resources\DuplicateGroupCollection;
use App\Http\Resources\StatsResource;
use App\Models\Client;
use App\Services\CsvImportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as ResponseFacade;
use League\Csv\Writer;

class ClientController extends Controller
{
    protected $csvImportService;

    public function __construct(CsvImportService $csvImportService)
    {
        $this->csvImportService = $csvImportService;
    }

    /**
     * Display a listing of clients with optional filtering
     */
    public function index(Request $request)
    {
        $query = Client::query();

        // Filter by duplicates
        if ($request->has('duplicates_only') && $request->boolean('duplicates_only')) {
            $query->duplicates();
        }

        // Filter by unique records
        if ($request->has('unique_only') && $request->boolean('unique_only')) {
            $query->unique();
        }

        // Filter by duplicate group
        if ($request->has('duplicate_group_id')) {
            $query->byDuplicateGroup($request->duplicate_group_id);
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $clients = $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);

        return (new ClientCollection($clients))
            ->additional([
                'stats' => new StatsResource($this->csvImportService->getImportStats())
            ]);
    }

    /**
     * Import CSV file with batch processing support
     */
    public function importCsv(CsvImportRequest $request)
    {
        $file = $request->file('csv_file');
        
        // Configure batch processing if provided
        if ($request->has('batch_size')) {
            $this->csvImportService->setBatchSize($request->input('batch_size'));
        }
        
        if ($request->has('max_errors')) {
            $this->csvImportService->setMaxErrors($request->input('max_errors'));
        }
        
        $result = $this->csvImportService->importCsv($file);

        $statusCode = $result['success'] ? 200 : 422;
        
        return response()->json($result, $statusCode);
    }

    /**
     * Export clients to CSV
     */
    public function exportCsv(Request $request)
    {
        $query = Client::query();

        // Filter by duplicates
        if ($request->has('duplicates_only') && $request->boolean('duplicates_only')) {
            $query->duplicates();
        }

        // Filter by unique records
        if ($request->has('unique_only') && $request->boolean('unique_only')) {
            $query->unique();
        }

        // Filter by duplicate group
        if ($request->has('duplicate_group_id')) {
            $query->byDuplicateGroup($request->duplicate_group_id);
        }

        $clients = $query->orderBy('created_at', 'desc')->get();

        // Create CSV
        $csv = Writer::createFromString('');
        $csv->insertOne(['company_name', 'email', 'phone_number', 'is_duplicate', 'duplicate_group_id', 'created_at']);

        foreach ($clients as $client) {
            $csv->insertOne([
                $client->company_name,
                $client->email,
                $client->phone_number,
                $client->is_duplicate ? 'Yes' : 'No',
                $client->duplicate_group_id ?? '',
                $client->created_at->format('Y-m-d H:i:s')
            ]);
        }

        $filename = 'clients_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return ResponseFacade::streamDownload(
            function () use ($csv) {
                echo $csv->toString();
            },
            $filename,
            ['Content-Type' => 'text/csv']
        );
    }

    /**
     * Get duplicate groups with pagination
     */
    public function getDuplicateGroups(Request $request)
    {
        // Get paginated duplicate groups
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        
        $duplicateGroups = Client::duplicates()
            ->select('duplicate_group_id')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('MIN(company_name) as representative_company')
            ->selectRaw('MIN(email) as representative_email')
            ->selectRaw('MIN(phone_number) as representative_phone')
            ->groupBy('duplicate_group_id')
            ->orderBy('count', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Transform the groups data to work with our resource
        $duplicateGroups->getCollection()->transform(function ($group) use ($request) {
            $groupData = $group->toArray();
            if ($request->has('include_clients') && $request->boolean('include_clients')) {
                $clients = Client::byDuplicateGroup($group->duplicate_group_id)
                    ->select(['id', 'company_name', 'email', 'phone_number', 'created_at'])
                    ->get();
                $groupData['clients'] = $clients;
            }
            return (object) $groupData;
        });
        
        return new DuplicateGroupCollection($duplicateGroups);
    }

    /**
     * Get clients for a specific duplicate group
     */
    public function getDuplicateGroupClients(Request $request, $groupId)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        $clients = Client::byDuplicateGroup($groupId)
            ->select(['id', 'company_name', 'email', 'phone_number', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return new ClientCollection($clients);
    }

    /**
     * Get import statistics
     */
    public function getStats()
    {
        return new StatsResource($this->csvImportService->getImportStats());
    }

    /**
     * Get batch processing configuration
     */
    public function getBatchConfig()
    {
        return new BatchConfigResource($this->csvImportService->getBatchConfig());
    }

    /**
     * Display the specified client
     */
    public function show(string $id)
    {
        $client = Client::findOrFail($id);
        
        // Get related duplicates if this is a duplicate
        $relatedClients = [];
        if ($client->is_duplicate && $client->duplicate_group_id) {
            $relatedClients = Client::byDuplicateGroup($client->duplicate_group_id)
                ->where('id', '!=', $client->id)
                ->get();
        }

        return new ClientWithDuplicatesResource([
            'client' => $client,
            'related_duplicates' => $relatedClients
        ]);
    }

    /**
     * Update the specified client
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'company_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'phone_number' => 'sometimes|required|string|max:255',
        ]);

        $client = Client::findOrFail($id);
        $client->update($request->only(['company_name', 'email', 'phone_number']));

        return (new ClientResource($client))
            ->additional([
                'message' => 'Client updated successfully'
            ]);
    }

    /**
     * Remove the specified client
     */
    public function destroy(string $id)
    {
        $client = Client::findOrFail($id);
        $client->delete();

        return response()->json([
            'success' => true,
            'message' => 'Client deleted successfully'
        ]);
    }

    /**
     * Delete all clients (for testing/reset purposes)
     */
    public function deleteAll()
    {
        Client::truncate();

        return response()->json([
            'success' => true,
            'message' => 'All clients deleted successfully'
        ]);
    }
}
