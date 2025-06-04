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
        'gender',
        'birthdate',
        'occupation_id',
        'phone',
        'phone_verified_at',
        'email',
        'email_verified_at',
        'social_provider',
        'social_id',
        'is_active',
        'last_login',
        'remember_token',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'phone_verified_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
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
    public function client_sessions()
    {
        return $this->hasMany(ClientSession::class);
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

    public function occupation()
    {
        return $this->belongsTo(Occupation::class);
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'gender',
                'birthdate',
                'occupation_id',
                'phone',
                'email',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('client');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }
}
