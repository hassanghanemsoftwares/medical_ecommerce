<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'subscription_plan_id',
        'payment_gateway_type',
        'payment_gateway_id',
        'starts_at',
        'ends_at',
    ];
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function subscription_plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function isActive(): bool
    {
        return now()->lessThanOrEqualTo($this->ends_at);
    }
}
