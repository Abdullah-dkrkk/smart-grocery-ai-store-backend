<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DietChart extends Model
{
    use HasFactory;

    protected $fillable = [
        'nutritionist_id',
        'client_id',
        'title',
        'description',
    ];

    public function nutritionist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nutritionist_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function days(): HasMany
    {
        return $this->hasMany(DietChartDay::class);
    }
}
