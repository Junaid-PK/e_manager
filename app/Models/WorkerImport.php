<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkerImport extends Model
{
    protected $fillable = [
        'file_name',
        'total_rows',
        'new_count',
        'active_count',
        'removed_count',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(WorkerImportEntry::class);
    }
}
