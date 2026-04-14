<?php

namespace App\Models;

use App\Models\Concerns\OwnedByAuthenticatedUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use OwnedByAuthenticatedUser;

    protected $fillable = [
        'user_id',
        'name',
        'tax_id',
        'contact_person',
        'email',
        'phone',
        'address',
        'notes',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
