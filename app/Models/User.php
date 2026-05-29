<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'avatar_url',
        'is_active',
        'approved_at',
        'notes',
    ];

    public function assignRole(string $role): void
    {
        if (!in_array($role, ['admin', 'vendor', 'customer', 'nutritionist'])) {
            throw new \InvalidArgumentException("Invalid role: {$role}");
        }

        $this->update(['role' => $role]);
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isVendor(): bool
    {
        return $this->role === 'vendor';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function isNutritionist(): bool
    {
        return $this->role === 'nutritionist';
    }

    public function healthProfile(): HasOne
    {
        return $this->hasOne(HealthProfile::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'vendor_id');
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function store(): HasOne
    {
        return $this->hasOne(VendorStore::class, 'vendor_id');
    }

    public function nutritionistClients(): HasMany
    {
        return $this->hasMany(NutritionistClient::class, 'nutritionist_id');
    }

    public function mealPlans(): HasMany
    {
        return $this->hasMany(MealPlan::class, 'nutritionist_id');
    }

    public function dietCharts(): HasMany
    {
        return $this->hasMany(DietChart::class, 'nutritionist_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'nutritionist_id');
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class, 'nutritionist_id');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'nutritionist_id');
    }

    public function nutritionistProfile(): HasOne
    {
        return $this->hasOne(NutritionistProfile::class, 'nutritionist_id');
    }
}
