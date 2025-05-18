<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ProductTag extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'product_tags'; 
    public $timestamps = true;

    protected $fillable = [
        'product_id',
        'tag_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['product_id', 'tag_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('product_tag');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }
}
