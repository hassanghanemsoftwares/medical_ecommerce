<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ReturnOrder extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'order_id',
        'return_order_number',
        'requested_at',
        'status',
        'reason',
        'created_by'
    ];

    protected $casts = [
        'requested_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function details()
    {
        return $this->hasMany(ReturnOrderDetail::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'return_order_number',
                'order_id',
                'requested_at',
                'status',
                'reason',
                'created_by'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('ReturnOrder');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }

    protected function status(): Attribute
    {
        return new Attribute(
            get: fn($value) => $this->getAllReturnStatuses()[$value] ?? null,
        );
    }

    public static function getAllReturnStatuses()
    {
        return [
            0 => [
                'name' => __('messages.return_status.Requested'),
                'description' => __('messages.return_status.Requested_description'),
                'color' => '#ffc107',
                'class' => 'warning',
            ],
            1 => [
                'name' => __('messages.return_status.Approved'),
                'description' => __('messages.return_status.Approved_description'),
                'color' => '#28a745',
                'class' => 'success',
            ],
            2 => [
                'name' => __('messages.return_status.Rejected'),
                'description' => __('messages.return_status.Rejected_description'),
                'color' => '#dc3545',
                'class' => 'danger',
            ],
            3 => [
                'name' => __('messages.return_status.Completed'),
                'description' => __('messages.return_status.Completed_description'),
                'color' => '#17a2b8',
                'class' => 'info',
            ],
        ];
    }

    public static function getStatusKey($statusName)
    {
        $statuses = self::getAllReturnStatuses();
        foreach ($statuses as $key => $status) {
            if ($status['name'] === $statusName) {
                return $key;
            }
        }
        return null;
    }

    public static function getStatusTransitions()
    {
        return [
            0 => [0, 1, 2],
            1 => [1, 3],
            2 => [2],
            3 => [3],
        ];
    }

    public static function getEnabledStatuses($currentStatus)
    {
        $allStatuses = self::getAllReturnStatuses();
        $transitions = self::getStatusTransitions();

        $enabled = [];
        if (isset($transitions[$currentStatus])) {
            foreach ($transitions[$currentStatus] as $statusKey) {
                if (isset($allStatuses[$statusKey])) {
                    $enabled[] = $allStatuses[$statusKey];
                }
            }
        }
        return $enabled;
    }

    public static function generateOrderNumber(): int
    {
        $maxOrderNumber = Order::max('return_order_number');
        return $maxOrderNumber ? $maxOrderNumber + 1 : 1;
    }
}
