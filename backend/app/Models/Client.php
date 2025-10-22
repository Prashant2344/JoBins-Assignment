<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'email',
        'phone_number',
        'is_duplicate',
        'duplicate_group_id',
        'import_metadata',
    ];

    protected $casts = [
        'is_duplicate' => 'boolean',
        'import_metadata' => 'array',
    ];

    /**
     * Scope to get only duplicate records
     */
    public function scopeDuplicates($query)
    {
        return $query->where('is_duplicate', true);
    }

    /**
     * Scope to get only unique records
     */
    public function scopeUnique($query)
    {
        return $query->where('is_duplicate', false);
    }

    /**
     * Scope to get records by duplicate group
     */
    public function scopeByDuplicateGroup($query, $groupId)
    {
        return $query->where('duplicate_group_id', $groupId);
    }

    /**
     * Get all records in the same duplicate group
     */
    public function duplicateGroup()
    {
        return $this->where('duplicate_group_id', $this->duplicate_group_id);
    }

    /**
     * Check if this record is a duplicate of another
     */
    public function isDuplicateOf(Client $other)
    {
        return $this->company_name === $other->company_name &&
               $this->email === $other->email &&
               $this->phone_number === $other->phone_number &&
               $this->id !== $other->id;
    }
}
