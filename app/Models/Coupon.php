<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Coupon extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'type',
        'value',
        'usage_limit',
        'usage_count',
        'min_order_amount',
        'status',
        'coupon_type',
        'client_id',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'type', 'value', 'usage_limit', 'usage_count', 'min_order_amount', 'status', 'coupon_type', 'client_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('coupon');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }

    public static function getAllCouponTypes($key = null)
    {
        $types = [
            __('messages.coupon_type.All clients'),
            __('messages.coupon_type.Specific users'),
            __('messages.coupon_type.First time'),
            __('messages.coupon_type.Order amount'),
            __('messages.coupon_type.Free delivery'),
        ];

        return is_null($key) ? $types : ($types[$key] ?? null);
    }
    protected function status(): Attribute
    {
        return new Attribute(
            get: function ($value) {
                $statuses = $this->getAllCouponStatus();

                return $statuses[$value] ?? null;
            },
        );
    }
    public static function getAllCouponStatus()
    {
        return [
            [
                'key' => "0",
                'name' => __('messages.coupon_status.PENDING'),
                'color' => '#ffc107',
                'class' => 'warning',
            ],
            [
                'key' => "1",
                'name' => __('messages.coupon_status.ACTIVE'),
                'color' => '#198754',
                'class' => 'success',
            ],
            [
                'key' => "2",
                'name' => __('messages.coupon_status.INACTIVE'),
                'color' => '#6c757d',
                'class' => 'secondary',
            ],
            [
                'key' => "3",
                'name' => __('messages.coupon_status.EXPIRED'),
                'color' => '#dc3545',
                'class' => 'danger',
            ],
            [
                'key' => "4",
                'name' => __('messages.coupon_status.USED'),
                'color' => '#0d6efd',
                'class' => 'primary',
            ],
            [
                'key' => "5",
                'name' => __('messages.coupon_status.CANCELED'),
                'color' => '#6f42c1',
                'class' => 'dark',
            ],
        ];
    }

    public static function getStatusKey($statusValue)
    {
        $statuses = self::getAllCouponStatus();
        return array_search($statusValue, array_column($statuses, 'name'));
    }


    public function canBeUsed()
    {
        $currentDate = Carbon::now();

        if ($this->status['key'] != 1) {
            return [false, 'Coupon is inactive'];
        }

        if ($this->valid_from && $this->valid_from > $currentDate) {
            return [false, 'Coupon is not yet valid'];
        }

        if ($this->valid_to && $this->valid_to->toDateString() < now()->toDateString()) {
            return [false, 'Coupon has expired'];
        }

        if ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit) {
            return [false, 'Coupon usage limit reached'];
        }

        // Add other checks here if needed

        return [true, 'Coupon is valid'];
    }


    public static function autoUpdateCouponsStatus(): void
    {
        $now = Carbon::now();
        $coupons = Coupon::all();
        foreach ($coupons as $coupon) {
            $currentStatus = (int) self::getStatusKey($coupon->status);
            $newStatus = $currentStatus;
            if ($coupon->valid_to && $coupon->valid_to->lt($now)) {
                $newStatus = 3; // EXPIRED
            } elseif ($coupon->usage_limit !== null && $coupon->usage_count >= $coupon->usage_limit) {
                $newStatus = 4; // USED
            } elseif (
                in_array($currentStatus, [3, 4]) &&
                (!$coupon->valid_to || $coupon->valid_to->gt($now)) &&
                ($coupon->usage_limit === null || $coupon->usage_count < $coupon->usage_limit)
            ) {
                $newStatus = 0; // PENDING
            }
            if ($newStatus !== $currentStatus) {
                $coupon->status = $newStatus;
                $coupon->save();
                activity('coupon')->performedOn($coupon)->log("Status auto-updated to " . $newStatus);
            }
        }
    }
}
