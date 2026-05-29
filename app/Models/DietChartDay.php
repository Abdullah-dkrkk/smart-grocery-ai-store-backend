<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DietChartDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'diet_chart_id',
        'day_number',
        'meals',
    ];

    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
            'meals' => 'array',
        ];
    }

    public function dietChart(): BelongsTo
    {
        return $this->belongsTo(DietChart::class);
    }
}
