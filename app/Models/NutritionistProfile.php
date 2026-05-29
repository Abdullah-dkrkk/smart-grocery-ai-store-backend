<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NutritionistProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'nutritionist_id',
        'bio',
        'specialization',
        'qualifications',
        'experience_years',
        'profile_image',
        'consultation_fee',
    ];

    protected function casts(): array
    {
        return [
            'experience_years' => 'integer',
            'consultation_fee' => 'decimal:2',
        ];
    }

    public function nutritionist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nutritionist_id');
    }
}
