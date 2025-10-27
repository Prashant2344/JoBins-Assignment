<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_clients' => $this->resource['total_clients'] ?? 0,
            'unique_clients' => $this->resource['unique_clients'] ?? 0,
            'duplicate_clients' => $this->resource['duplicate_clients'] ?? 0,
            'duplicate_groups' => $this->resource['duplicate_groups'] ?? 0,
            'last_import' => $this->resource['last_import'] ?? null,
            'import_count' => $this->resource['import_count'] ?? 0,
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
        ];
    }
}
