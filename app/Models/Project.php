<?php

namespace App\Models;

use App\Models\Concerns\OwnedByAuthenticatedUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use OwnedByAuthenticatedUser;

    protected $fillable = [
        'user_id',
        'company_id',
        'client_id',
        'name',
        'code',
        'description',
        'location',
        'status',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
