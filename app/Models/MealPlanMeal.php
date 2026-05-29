<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealPlanMeal extends Model
{
    use HasFactory;

    protected $fillable = [
        'meal_plan_id',
        'day',
        'meal_type',
        'name',
        'calories',
        'protein_g',
        'carbs_g',
        'fat_g',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'day' => 'integer',
            'calories' => 'integer',
            'protein_g' => 'float',
            'carbs_g' => 'float',
            'fat_g' => 'float',
        ];
    }

    public function mealPlan(): BelongsTo
    {
        return $this->belongsTo(MealPlan::class);
    }
}
