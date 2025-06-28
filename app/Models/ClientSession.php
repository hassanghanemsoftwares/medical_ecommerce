<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ClientSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'notification_token',
        'device_id',
        'ip_address',
        'user_agent',
        'is_active',
        'last_activity',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'last_activity' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }


    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }
}
