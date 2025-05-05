<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'device_id',
        'token',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function device()
    {
        return $this->belongsTo(ClientDevice::class);
    }
}
