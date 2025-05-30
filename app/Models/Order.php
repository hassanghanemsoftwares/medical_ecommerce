<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Order extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'order_number',
        'client_id',
        'is_cart',
        'address_id',
        'coupon_id',
        'coupon_value',
        'coupon_type',
        'address_info',
        'notes',
        'payment_method',
        'payment_status',
        'delivery_amount',
        'status',
        'is_view',
    ];

    protected $casts = [
        'address_info' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['order_number', 'client_id', 'is_cart', 'address_id', 'coupon_id', 'coupon_value', 'coupon_type', 'address_info', 'notes', 'payment_method', 'payment_status', 'delivery_amount', 'status', 'is_view'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('order');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }
    public function getSubtotalAttribute()
    {
        return $this->orderDetails->sum(function ($orderDetail) {
            return $orderDetail->getTotalAttribute();
        });
    }


    public function getGrandTotalAttribute()
    {
        $subtotal = $this->subtotal ?? 0;
        $delivery_amount = $this->delivery_amount ?? 0;
        $discount = 0;
        if ($this->coupon_value && $this->coupon_type) {
            if ($this->coupon_type === 'fixed') {
                $discount = $this->coupon_value;
            } elseif ($this->coupon_type === 'percentage') {
                $discount = ($subtotal * $this->coupon_value) / 100;
            }
        }
        $grandTotal = $subtotal - $discount + $delivery_amount;
        return $grandTotal > 0 ? $grandTotal : 0;
    }



    protected function status(): Attribute
    {
        return new Attribute(
            get: function ($value) {
                $statuses = $this->getAllOrderStatus();

                return $statuses[$value] ?? null;
            },
        );
    }

    public static function getAllOrderStatus()
    {
        return [
            [
                'name' => __('messages.order_status.Pending'),
                'description' => __('messages.order_status.Pending_description'),
                'color' => '#ffc107',
                'class' => 'warning',
            ],
            [
                'name' => __('messages.order_status.Confirmed'),
                'description' => __('messages.order_status.Confirmed_description'),
                'color' => '#007bff',
                'class' => 'primary',
            ],
            [
                'name' => __('messages.order_status.Processing'),
                'description' => __('messages.order_status.Processing_description'),
                'color' => '#17a2b8',
                'class' => 'info',
            ],
            [
                'name' => __('messages.order_status.On Hold'),
                'description' => __('messages.order_status.On Hold_description'),
                'color' => '#6c757d',
                'class' => 'secondary',
            ],
            [
                'name' => __('messages.order_status.Shipped'),
                'description' => __('messages.order_status.Shipped_description'),
                'color' => '#6610f2',
                'class' => 'primary',
            ],
            [
                'name' => __('messages.order_status.Delivered'),
                'description' => __('messages.order_status.Delivered_description'),
                'color' => '#28a745',
                'class' => 'success',
            ],
            [
                'name' => __('messages.order_status.Failed'),
                'description' => __('messages.order_status.Failed_description'),
                'color' => '#dc3545',
                'class' => 'danger',
            ],
            [
                'name' => __('messages.order_status.Cancelled By Admin'),
                'description' => __('messages.order_status.Cancelled By Admin_description'),
                'color' => '#dc3545',
                'class' => 'danger',
            ],
            [
                'name' => __('messages.order_status.Cancelled By Customer'),
                'description' => __('messages.order_status.Cancelled By Customer_description'),
                'color' => '#dc3545',
                'class' => 'danger',
            ],
            [
                'name' => __('messages.order_status.Returned'),
                'description' => __('messages.order_status.Returned_description'),
                'color' => '#17a2b8',
                'class' => 'info',
            ],
            [
                'name' => __('messages.order_status.Completed'),
                'description' => __('messages.order_status.Completed_description'),
                'color' => '#28a745',
                'class' => 'success',
            ],
        ];
    }

    public static function getPaymentStatus($key = null)
    {
        $statuses = [
            __('messages.payment_status.Pending'),
            __('messages.payment_status.Paid'),
            __('messages.payment_status.Failed'),
            __('messages.payment_status.Refunded'),
        ];

        return is_null($key) ? $statuses : ($statuses[$key] ?? null);
    }
    public static function getPaymentMethods($key = null)
    {
        $paymentMethods = [
            __('messages.payment_methods.COD'),
        ];

        return is_null($key) ? $paymentMethods : ($paymentMethods[$key] ?? null);
    }
    public static function getStatusKey($statusValue)
    {
        $statuses = self::getAllOrderStatus();
        return array_search($statusValue, array_column($statuses, 'name'));
    }
    public static function generateOrderNumber(): int
    {
        // Get the max order_number from existing orders
        $maxOrderNumber = Order::max('order_number');

        // If no orders yet, start from 1, else increment
        return $maxOrderNumber ? $maxOrderNumber + 1 : 1;
    }
}
