<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerImportEntry extends Model
{
    protected $fillable = [
        'worker_import_id',
        'worker_id',
        'full_name',
        'nie',
        'bank_account',
        'status_at_import',
    ];

    public function workerImport(): BelongsTo
    {
        return $this->belongsTo(WorkerImport::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}
