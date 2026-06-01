<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'max_discount_amount',
        'min_order_amount',
        'max_uses',
        'per_user_limit',
        'used_count',
        'applies_to',
        'applicable_ids',
        'minimum_items',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_uses' => 'integer',
            'per_user_limit' => 'integer',
            'used_count' => 'integer',
            'minimum_items' => 'integer',
            'applicable_ids' => 'array',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(DiscountUserUsage::class, 'discount_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'discount_user_usage', 'discount_id', 'user_id')
            ->withPivot('used_at');
    }

    public function isValid(?User $user = null, ?float $subtotal = null, ?int $itemCount = null, ?array $productCategoryIds = null): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        $now = Carbon::now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }

        if ($user && $this->per_user_limit !== null) {
            $userUsageCount = $this->usageRecords()
                ->where('user_id', $user->id)
                ->count();

            if ($userUsageCount >= $this->per_user_limit) {
                return false;
            }
        }

        if ($subtotal !== null && $this->min_order_amount !== null && $subtotal < $this->min_order_amount) {
            return false;
        }

        if ($itemCount !== null && $this->minimum_items !== null && $itemCount < $this->minimum_items) {
            return false;
        }

        if ($productCategoryIds !== null && $this->applies_to !== 'all') {
            $overlap = array_intersect($productCategoryIds, $this->applicable_ids ?? []);
            if (empty($overlap)) {
                return false;
            }
        }

        return true;
    }

    public function calculateDiscount(float $subtotal): float
    {
        if ($this->min_order_amount !== null && $subtotal < $this->min_order_amount) {
            return 0;
        }

        $discount = 0;

        if ($this->type === 'percentage') {
            $discount = round($subtotal * ($this->value / 100), 2);
        } else {
            $discount = $this->value;
        }

        if ($discount > $subtotal) {
            $discount = $subtotal;
        }

        if ($this->max_discount_amount !== null && $discount > (float) $this->max_discount_amount) {
            $discount = (float) $this->max_discount_amount;
        }

        return round($discount, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        $now = Carbon::now();
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses');
            });
    }
}
