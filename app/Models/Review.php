<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Review extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'client_id',
        'product_id',
        'rating',
        'comment',
        'is_active',
        'is_view',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['client_id', 'product_id', 'rating', 'comment', 'is_active', 'is_view'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('review');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }
}
