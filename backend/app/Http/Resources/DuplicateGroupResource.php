<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DuplicateGroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'group_id' => $this->duplicate_group_id,
            'count' => $this->count,
            'representative_company' => $this->representative_company,
            'representative_email' => $this->representative_email,
            'representative_phone' => $this->representative_phone,
            'clients' => $this->when(
                $request->has('include_clients') && $request->boolean('include_clients'),
                ClientResource::collection($this->clients ?? [])
            ),
        ];
    }
}
