<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorStore extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'store_name',
        'store_description',
        'store_logo_url',
        'store_banner_url',
        'return_policy',
        'shipping_policy',
        'contact_email',
        'contact_phone',
        'is_approved',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }
}
