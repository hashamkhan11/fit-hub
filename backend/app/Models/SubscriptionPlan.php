<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'monthly_price',
        'yearly_price',
        'stripe_price_id_monthly',
        'stripe_price_id_yearly',
        'member_limit',
        'staff_limit',
        'features',
        'has_hardware_access',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'yearly_price' => 'decimal:2',
            'features' => 'array',
            'has_hardware_access' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function gyms(): HasMany
    {
        return $this->hasMany(Gym::class);
    }

    public function isSyncedToStripe(): bool
    {
        return ! empty($this->stripe_price_id_monthly);
    }
}
