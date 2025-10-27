<?php

namespace Tests\Unit;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_be_created()
    {
        $client = Client::create([
            'company_name' => 'Test Company',
            'email' => 'test@example.com',
            'phone_number' => '+1-555-0123',
            'is_duplicate' => false,
        ]);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals('Test Company', $client->company_name);
        $this->assertEquals('test@example.com', $client->email);
        $this->assertEquals('+1-555-0123', $client->phone_number);
        $this->assertFalse($client->is_duplicate);
    }

    public function test_client_duplicate_scopes()
    {
        // Create unique client
        Client::create([
            'company_name' => 'Unique Company',
            'email' => 'unique@example.com',
            'phone_number' => '+1-555-0001',
            'is_duplicate' => false,
        ]);

        // Create duplicate client
        Client::create([
            'company_name' => 'Duplicate Company',
            'email' => 'duplicate@example.com',
            'phone_number' => '+1-555-0002',
            'is_duplicate' => true,
            'duplicate_group_id' => 'group-1',
        ]);

        $this->assertEquals(1, Client::unique()->count());
        $this->assertEquals(1, Client::duplicates()->count());
    }

    public function test_client_is_duplicate_of_method()
    {
        $client1 = Client::create([
            'company_name' => 'Same Company',
            'email' => 'same@example.com',
            'phone_number' => '+1-555-0123',
            'is_duplicate' => false,
        ]);

        $client2 = Client::create([
            'company_name' => 'Same Company',
            'email' => 'same@example.com',
            'phone_number' => '+1-555-0123',
            'is_duplicate' => true,
        ]);

        $this->assertTrue($client2->isDuplicateOf($client1));
        $this->assertFalse($client1->isDuplicateOf($client1));
    }
}
