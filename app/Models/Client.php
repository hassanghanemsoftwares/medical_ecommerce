<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Client extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'uid',
        'gender',
        'is_active',
        'last_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login' => 'datetime',
    ];
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function app_devices()
    {
        return $this->hasMany(ClientDevice::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }
 
    public function notification_user()
    {
        return $this->hasMany(Notification::class);
    }
    public function access_tokens()
    {
        return $this->hasMany(AccessToken::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'phone', 'email', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('client');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }
}
