<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class SearchLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'category_id',
        'brand_id',
        'size_id',
        'color_id',
        'text',
    ];

    public function device()
    {
        return $this->belongsTo(ClientDevice::class, 'device_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function size()
    {
        return $this->belongsTo(Size::class, 'size_id');
    }

    public function color()
    {
        return $this->belongsTo(Color::class, 'color_id');
    }

  
}
