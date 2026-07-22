<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait OwnedByAuthenticatedUser
{
    protected static function bootOwnedByAuthenticatedUser(): void
    {
        static::addGlobalScope('ownedByUser', function (Builder $builder) {
            if (! Auth::check()) {
                return;
            }
            $user = Auth::user();
            if ($user === null || $user->hasRole('admin')) {
                return;
            }
            $table = $builder->getModel()->getTable();
            $module = match ($table) {
                'invoices' => 'invoices',
                'expenses' => 'expenses',
                'bank_movements' => 'movements',
                'bank_accounts' => 'bank_accounts',
                'credit_lines' => 'credit_lines',
                'companies', 'clients', 'projects' => 'companies_clients',
                default => null,
            };
            $ownerIds = $module === null
                ? [$user->id]
                : $user->accessibleOwnerIds($module);

            if ($ownerIds !== null) {
                $builder->whereIn($table.'.user_id', $ownerIds);
            }
        });

        static::creating(function (Model $model) {
            if ($model->getAttribute('user_id') !== null && $model->getAttribute('user_id') !== '') {
                return;
            }
            if (! Auth::check()) {
                return;
            }
            $model->setAttribute('user_id', Auth::id());
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
