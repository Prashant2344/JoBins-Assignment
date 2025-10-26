<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_name' => $this->faker->company(),
            'email' => $this->faker->companyEmail(),
            'phone_number' => $this->faker->phoneNumber(),
            'is_duplicate' => false,
            'duplicate_group_id' => null,
            'import_metadata' => [
                'batch_id' => $this->faker->uuid(),
                'row_number' => $this->faker->numberBetween(1, 1000),
                'imported_at' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d\TH:i:s.u\Z'),
            ],
        ];
    }

    /**
     * Indicate that the client is a duplicate.
     */
    public function duplicate(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_duplicate' => true,
            'duplicate_group_id' => $this->faker->uuid(),
        ]);
    }

    /**
     * Indicate that the client has import metadata.
     */
    public function withImportMetadata(): static
    {
        return $this->state(fn (array $attributes) => [
            'import_metadata' => [
                'batch_id' => $this->faker->uuid(),
                'row_number' => $this->faker->numberBetween(1, 1000),
                'imported_at' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d\TH:i:s.u\Z'),
            ],
        ]);
    }
}
