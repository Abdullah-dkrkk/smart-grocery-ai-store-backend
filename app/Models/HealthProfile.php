<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HealthProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'age',
        'weight',
        'height',
        'bmi',
        'goals',
        'allergies',
        'dietary_type',
        'activity_level',
        'medical_conditions',
        'daily_calorie_target',
    ];

    protected function casts(): array
    {
        return [
            'allergies' => 'array',
            'weight' => 'decimal:1',
            'height' => 'decimal:1',
            'bmi' => 'decimal:1',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
