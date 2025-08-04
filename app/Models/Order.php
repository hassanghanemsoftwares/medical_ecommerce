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
        'is_preorder',
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
    public function returnOrders()
    {
        return $this->hasMany(ReturnOrder::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'order_number',
                'client_id',
                'is_cart',
                'is_preorder',
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
                'is_view'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('Order');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }
    public function getSubtotalAttribute()
    {
        $subtotal = $this->orderDetails->sum(function ($orderDetail) {
            return $orderDetail->getTotalAttribute();
        });

        return round($subtotal, 2);
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

        return round(max($grandTotal, 0), 2);
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
            [ //0
                'name' => __('messages.order_status.Pending'),
                'description' => __('messages.order_status.Pending_description'),
                'color' => '#ffc107',
                'class' => 'warning',
            ],
            [ //1
                'name' => __('messages.order_status.Confirmed'),
                'description' => __('messages.order_status.Confirmed_description'),
                'color' => '#007bff',
                'class' => 'primary',
            ],
            [ //2
                'name' => __('messages.order_status.Processing'),
                'description' => __('messages.order_status.Processing_description'),
                'color' => '#17a2b8',
                'class' => 'info',
            ],
            [ //3
                'name' => __('messages.order_status.On Hold'),
                'description' => __('messages.order_status.On Hold_description'),
                'color' => '#6c757d',
                'class' => 'secondary',
            ],
            [ //4
                'name' => __('messages.order_status.Shipped'),
                'description' => __('messages.order_status.Shipped_description'),
                'color' => '#6610f2',
                'class' => 'primary',
            ],
            [ //5
                'name' => __('messages.order_status.Delivered'),
                'description' => __('messages.order_status.Delivered_description'),
                'color' => '#28a745',
                'class' => 'success',
            ],
            [ //6
                'name' => __('messages.order_status.Failed'),
                'description' => __('messages.order_status.Failed_description'),
                'color' => '#dc3545',
                'class' => 'danger',
            ],
            [ //7
                'name' => __('messages.order_status.Cancelled By Admin'),
                'description' => __('messages.order_status.Cancelled By Admin_description'),
                'color' => '#dc3545',
                'class' => 'danger',
            ],
            [ //8
                'name' => __('messages.order_status.Cancelled By Customer'),
                'description' => __('messages.order_status.Cancelled By Customer_description'),
                'color' => '#dc3545',
                'class' => 'danger',
            ],
            [ //9
                'name' => __('messages.order_status.Returned'),
                'description' => __('messages.order_status.Returned_description'),
                'color' => '#17a2b8',
                'class' => 'info',
            ],
            [ //10
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
    public const STATUS_TRANSITIONS = [
        0 => [0, 1, 7],
        1 => [1, 2, 3, 7],
        2 => [2, 3, 4, 7],
        3 => [3, 2, 7],
        4 => [4, 5, 6, 7],
        5 => [5, 10],
        6 => [6, 7],
        7 => [7],
        8 => [8],
        9 => [9],
        10 => [10],
    ];

    public function canTransitionTo(int $newStatus): bool
    {
        $current = $this->getStatusKey($this->status['name']);
        return in_array($newStatus, self::STATUS_TRANSITIONS[$current] ?? []);
    }
    public static function generateOrderNumber(): int
    {
        $maxOrderNumber = Order::max('order_number');
        return $maxOrderNumber ? $maxOrderNumber + 1 : 1;
    }
    public static function validateAndPrepareProducts(array $products, float &$total): array
    {
        $validatedProducts = [];

        foreach ($products as $item) {
            $variant = Variant::with('product')->find($item['variant_id']);

            if (!$variant || !$variant->product) {
                throw new \Exception(__('messages.order.invalid_variant', ['sku' => $item['variant_id']]));
            }

            $price = $variant->product->price;
            $discount = $variant->product->discount;
            $discountedPrice = $price - ($price * $discount / 100);
            $total += $discountedPrice * $item['quantity'];

            $validatedProducts[] = [
                'variant' => $variant,
                'quantity' => $item['quantity'],
                'price' => $price,
            ];
        }

        return $validatedProducts;
    }

    public static function applyCouponIfExists(?string $couponCode, float $total): ?Coupon
    {
        if (!$couponCode) return null;

        $coupon = Coupon::where('code', $couponCode)->first();

        if (!$coupon) {
            throw new \Exception(__('messages.order.invalid_coupon'));
        }

        [$canUse, $reason] = $coupon->canBeUsed();

        if (!$canUse) {
            throw new \Exception($reason);
        }

        if ($coupon->min_order_amount !== null && $total < $coupon->min_order_amount) {
            throw new \Exception(__('messages.order.min_amount_not_met', [
                'amount' => $coupon->min_order_amount,
            ]));
        }

        return $coupon;
    }
    public function returnedQuantities(): array
    {
        if (!$this->relationLoaded('returnOrders')) {
            $this->load('returnOrders.details');
        }

        $quantities = [];

        foreach ($this->returnOrders as $returnOrder) {
            // Use raw status integer, not transformed object
            if ((int)$returnOrder->getAttribute('status') !== 1) {
                continue;
            }

            foreach ($returnOrder->details as $detail) {
                $variantId = $detail->variant_id;
                $quantities[$variantId] = ($quantities[$variantId] ?? 0) + $detail->quantity;
            }
        }

        return $quantities;
    }
}
