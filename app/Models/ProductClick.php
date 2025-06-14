<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductClick extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'product_id',
    ];

    public function client_sessions()
    {
        return $this->hasMany(ClientSession::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
