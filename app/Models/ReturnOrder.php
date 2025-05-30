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
        'client_id',
        'requested_at',
        'processed_at',
        'status',
        'reason',
        'refund_amount',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'refund_amount' => 'decimal:2',
    ];

    // Relationships
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

    // Activity Log Options
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'order_id',
                'client_id',
                'requested_at',
                'processed_at',
                'status',
                'reason',
                'refund_amount',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('return_order');
    }

    // Activity description
    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }

    // Status attribute accessor (casts numeric status to descriptive array)
    protected function status(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $this->getAllReturnStatuses()[$value] ?? null,
        );
    }

    // Return statuses with localization and UI info
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

    // Get status key by name (inverse lookup)
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

    // Allowed status transitions
    private static function getStatusTransitions()
    {
        return [
            0 => [0, 1, 2],  // Requested -> Requested, Approved, Rejected
            1 => [1, 3],     // Approved -> Approved, Completed
            2 => [2],        // Rejected -> Rejected (terminal)
            3 => [3],        // Completed -> Completed (terminal)
        ];
    }

    // Get enabled statuses based on current
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
}
