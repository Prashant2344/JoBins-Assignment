<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing clients to avoid conflicts
        Client::truncate();
        
        // Create 20 unique clients (all will have metadata by default)
        Client::factory(20)->create();

        // Create 5 additional clients with specific metadata
        Client::factory(5)->withImportMetadata()->create();

        // Create some duplicate clients for testing
        $duplicateGroupId = 'duplicate-group-' . uniqid();
        
        // Create 3 duplicate clients in the same group
        Client::factory(3)->duplicate()->create([
            'duplicate_group_id' => $duplicateGroupId,
            'company_name' => 'TechCorp Solutions',
            'email' => 'info@techcorp.com',
            'phone_number' => '+1-555-0123',
            'import_metadata' => [
                'batch_id' => 'duplicate-batch-001',
                'row_number' => 100,
                'imported_at' => now()->subDays(2)->format('Y-m-d\TH:i:s.u\Z'),
            ],
        ]);

        // Create another group of duplicates
        $duplicateGroupId2 = 'duplicate-group-' . uniqid();
        Client::factory(2)->duplicate()->create([
            'duplicate_group_id' => $duplicateGroupId2,
            'company_name' => 'Global Industries Ltd',
            'email' => 'contact@globalindustries.com',
            'phone_number' => '+1-555-0456',
            'import_metadata' => [
                'batch_id' => 'duplicate-batch-002',
                'row_number' => 200,
                'imported_at' => now()->subDays(1)->format('Y-m-d\TH:i:s.u\Z'),
            ],
        ]);

        // Create some specific companies for testing
        $specificCompanies = [
            [
                'company_name' => 'Acme Corporation',
                'email' => 'hello@acme.com',
                'phone_number' => '+1-555-0001',
                'is_duplicate' => false,
                'duplicate_group_id' => null,
                'import_metadata' => [
                    'batch_id' => 'batch-001',
                    'row_number' => 1,
                    'imported_at' => now()->subDays(5)->format('Y-m-d\TH:i:s.u\Z'),
                ],
            ],
            [
                'company_name' => 'Beta Solutions Inc',
                'email' => 'info@betasolutions.com',
                'phone_number' => '+1-555-0002',
                'is_duplicate' => false,
                'duplicate_group_id' => null,
                'import_metadata' => [
                    'batch_id' => 'batch-001',
                    'row_number' => 2,
                    'imported_at' => now()->subDays(5)->format('Y-m-d\TH:i:s.u\Z'),
                ],
            ],
            [
                'company_name' => 'Gamma Technologies',
                'email' => 'support@gammatech.com',
                'phone_number' => '+1-555-0003',
                'is_duplicate' => false,
                'duplicate_group_id' => null,
                'import_metadata' => [
                    'batch_id' => 'batch-002',
                    'row_number' => 15,
                    'imported_at' => now()->subDays(3)->format('Y-m-d\TH:i:s.u\Z'),
                ],
            ],
        ];

        foreach ($specificCompanies as $company) {
            Client::create($company);
        }

        // Count records with metadata
        $withMetadata = Client::whereNotNull('import_metadata')->count();
        $totalRecords = Client::count();

        $this->command->info("Created {$totalRecords} client records total.");
        $this->command->info("Records with import metadata: {$withMetadata}");
        $this->command->info("Records without metadata: " . ($totalRecords - $withMetadata));
        
        if ($withMetadata === $totalRecords) {
            $this->command->info("✅ All records have import metadata!");
        } else {
            $this->command->warn("⚠️  Some records are missing import metadata!");
        }
    }
}
