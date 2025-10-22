<?php

namespace App\Http\Controllers;

use App\Http\Requests\CsvImportRequest;
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
        $clients = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $clients,
            'stats' => $this->csvImportService->getImportStats()
        ]);
    }

    /**
     * Import CSV file
     */
    public function importCsv(CsvImportRequest $request)
    {
        $file = $request->file('csv_file');
        $result = $this->csvImportService->importCsv($file);

        $statusCode = $result['success'] ? 200 : 422;
        
        return response()->json($result, $statusCode);
    }

    /**
     * Get duplicate groups
     */
    public function getDuplicateGroups()
    {
        $duplicateGroups = Client::duplicates()
            ->select('duplicate_group_id')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('duplicate_group_id')
            ->get();

        $groupsWithDetails = [];
        foreach ($duplicateGroups as $group) {
            $clients = Client::byDuplicateGroup($group->duplicate_group_id)->get();
            $groupsWithDetails[] = [
                'group_id' => $group->duplicate_group_id,
                'count' => $group->count,
                'clients' => $clients
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $groupsWithDetails
        ]);
    }

    /**
     * Get import statistics
     */
    public function getStats()
    {
        return response()->json([
            'success' => true,
            'data' => $this->csvImportService->getImportStats()
        ]);
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

        return response()->json([
            'success' => true,
            'data' => [
                'client' => $client,
                'related_duplicates' => $relatedClients
            ]
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

        return response()->json([
            'success' => true,
            'message' => 'Client updated successfully',
            'data' => $client
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
