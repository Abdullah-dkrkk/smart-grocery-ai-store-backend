<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'message',
        'response',
        'type',
        'context',
        'metadata',
        'image_url',
        'response_time_ms',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'response_time_ms' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isImageQuery(): bool
    {
        return $this->type === 'image';
    }

    public function isTextQuery(): bool
    {
        return $this->type === 'text';
    }
}
