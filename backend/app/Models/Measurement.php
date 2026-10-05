<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Measurement extends Model
{
    use BelongsToGym;
    use HasFactory;

    protected $fillable = [
        'gym_id',
        'member_id',
        'recorded_at',
        'weight_kg',
        'body_fat_percentage',
        'chest_cm',
        'waist_cm',
        'hips_cm',
        'arms_cm',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'date',
            'weight_kg' => 'decimal:2',
            'body_fat_percentage' => 'decimal:2',
            'chest_cm' => 'decimal:2',
            'waist_cm' => 'decimal:2',
            'hips_cm' => 'decimal:2',
            'arms_cm' => 'decimal:2',
        ];
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
