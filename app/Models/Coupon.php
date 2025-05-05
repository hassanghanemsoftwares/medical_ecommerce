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
    protected function coupon_type(): Attribute
    {
        return new Attribute(
            get: fn($value) => $this->getAllCouponTypes()[$value],
        );
    }
    public static function getAllCouponTypes()
    {
        return [
            __('messages.coupon_type.All clients'),
            __('messages.coupon_type.Specific users'),
            __('messages.coupon_type.First time'),
            __('messages.coupon_type.Order amount'),
            __('messages.coupon_type.Free delivery'),
        ];
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
                'name' => __('messages.coupon_status.PENDING'),
                'color' => '#ffc107',
                'class' => 'warning',
            ],
            [
                'name' => __('messages.coupon_status.ACTIVE'),
                'color' => '#198754',
                'class' => 'success',
            ],
            [
                'name' => __('messages.coupon_status.INACTIVE'),
                'color' => '#6c757d',
                'class' => 'secondary',
            ],
            [
                'name' => __('messages.coupon_status.EXPIRED'),
                'color' => '#dc3545',
                'class' => 'danger',
            ],
            [
                'name' => __('messages.coupon_status.USED'),
                'color' => '#0d6efd',
                'class' => 'primary',
            ],
            [
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
    private static function getStatusTransitions()
    {
        return [
            0 => [0, 1, 2, 5],
            1 => [1, 2, 5],
            2 => [0, 1, 2],
            3 => [3],
            4 => [4],
            5 => [5],
        ];
    }

    public static function getEnabledStatuses($currentStatus)
    {
        $allStatuses = self::getAllCouponStatus();
        $transitions = self::getStatusTransitions();

        $enabledStatuses = [];
        if (isset($transitions[$currentStatus])) {
            $enabledStatuses = array_filter($allStatuses, function ($status) use ($transitions, $currentStatus) {
                return in_array(self::getStatusKey($status['name']), $transitions[$currentStatus]);
            });
        }
        return $enabledStatuses;
    }

    public function canBeUsed()
    {
        $currentDate = Carbon::now();

        // Check if coupon is active
        if ($this->status != 1) {
            return false;
        }

        // Check date range
        if ($this->valid_from && $this->valid_from > $currentDate) {
            return false;
        }
        if ($this->valid_to && $this->valid_to < $currentDate) {
            return false;
        }

        // Check usage limit
        if ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        // Additional checks based on coupon types or other logic can be added here

        return true;
    }

    public static function autoUpdateCouponsStatus()
    {
        $currentDate = Carbon::now();
        $coupons = Coupon::where("status", "<", 3)->get();
        foreach ($coupons as $coupon) {
            if ($coupon->valid_to && $coupon->valid_to < $currentDate) {
                $coupon->status = 3;
            }
            if ($coupon->usage_limit !== null && $coupon->usage_count >= $coupon->usage_limit) {
                $coupon->status = 4;
            }
            $coupon->save();
        }

        $coupons = Coupon::where("status", "=", 3)->get();
        foreach ($coupons as $coupon) {
            if ($coupon->valid_to && $coupon->valid_to > $currentDate) {
                $coupon->status = 0;
            }
            if ($coupon->usage_limit == null && $coupon->usage_count < $coupon->usage_limit) {
                $coupon->status = 0;
            }
            $coupon->save();
        }


        $coupons = Coupon::where("status", "=", 4)->get();
        foreach ($coupons as $coupon) {
            if ($coupon->valid_to && $coupon->valid_to > $currentDate) {
                $coupon->status = 0;
            }
            if ($coupon->usage_limit == null && $coupon->usage_count < $coupon->usage_limit) {
                $coupon->status = 0;
            }
            $coupon->save();
        }
    }
}
