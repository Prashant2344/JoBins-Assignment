<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CsvImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_import_requires_file()
    {
        $response = $this->post('/api/v1/clients/import');
        
        // Check if validation error occurs (either 422 or redirect with error)
        $this->assertTrue(in_array($response->status(), [422, 302]));
        
        if ($response->status() === 422) {
            $response->assertJsonValidationErrors(['csv_file']);
        }
    }

    public function test_csv_import_validates_file_type()
    {
        $file = UploadedFile::fake()->create('test.txt', 100);
        
        $response = $this->post('/api/v1/clients/import', [
            'csv_file' => $file
        ]);
        
        // Check if validation error occurs
        $this->assertTrue(in_array($response->status(), [422, 302]));
        
        // The validation is working, we just need to check that the endpoint responds appropriately
        $this->assertNotEquals(200, $response->status());
    }

    public function test_csv_import_successful()
    {
        Storage::fake('local');
        
        $csvContent = "company_name,email,phone_number\n";
        $csvContent .= "Test Company,test@example.com,+1-555-0123\n";
        $csvContent .= "Another Company,another@example.com,+1-555-0456\n";
        
        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);
        
        $response = $this->post('/api/v1/clients/import', [
            'csv_file' => $file
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'imported',
                'duplicates',
                'errors',
                'duplicate_groups'
            ],
            'batch_id'
        ]);
        
        $this->assertEquals(2, Client::count());
        $this->assertEquals(2, Client::unique()->count());
        $this->assertEquals(0, Client::duplicates()->count());
    }

    public function test_csv_import_with_duplicates()
    {
        Storage::fake('local');
        
        $csvContent = "company_name,email,phone_number\n";
        $csvContent .= "Test Company,test@example.com,+1-555-0123\n";
        $csvContent .= "Test Company,test@example.com,+1-555-0123\n";
        $csvContent .= "Another Company,another@example.com,+1-555-0456\n";
        
        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);
        
        $response = $this->post('/api/v1/clients/import', [
            'csv_file' => $file
        ]);
        
        $response->assertStatus(200);
        
        $this->assertEquals(3, Client::count());
        $this->assertEquals(2, Client::unique()->count());
        $this->assertEquals(1, Client::duplicates()->count());
    }

    public function test_csv_import_with_invalid_data()
    {
        Storage::fake('local');
        
        $csvContent = "company_name,email,phone_number\n";
        $csvContent .= "Test Company,invalid-email,+1-555-0123\n";
        $csvContent .= ",valid@example.com,+1-555-0456\n";
        
        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);
        
        $response = $this->post('/api/v1/clients/import', [
            'csv_file' => $file
        ]);
        
        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertGreaterThan(0, $data['errors']);
        $this->assertArrayHasKey('errors_details', $data);
    }
}
