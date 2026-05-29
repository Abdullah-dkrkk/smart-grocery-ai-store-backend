<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MealPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nutritionist_id',
        'client_id',
        'name',
        'description',
        'duration_days',
        'daily_calories',
        'is_template',
    ];

    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'daily_calories' => 'integer',
            'is_template' => 'boolean',
        ];
    }

    public function nutritionist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nutritionist_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function meals(): HasMany
    {
        return $this->hasMany(MealPlanMeal::class);
    }
}
