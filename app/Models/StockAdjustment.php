<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class StockAdjustment extends Model
{
    use HasFactory, LogsActivity;


    protected $fillable = [
        'variant_id',
        'warehouse_id',
        'shelf_id',
        'type',
        'quantity',
        'cost_per_item',
        'reason',
        'adjusted_by',
        'reference_id',
        'reference_type',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function shelf()
    {
        return $this->belongsTo(Shelf::class);
    }

    public function adjustedBy()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }


    public function reference()
    {
        return $this->morphTo();
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'variant_id',
                'warehouse_id',
                'shelf_id',
                'type',
                'quantity',
                'cost_per_item',
                'reason',
                'adjusted_by',
                'reference_id',
                'reference_type',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('stock_adjustment');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }

    public static function updateStockQuantity($variantId, $warehouseId, $shelfId, $quantityChange): Stock
    {
        $stock = Stock::firstOrNew([
            'variant_id' => $variantId,
            'warehouse_id' => $warehouseId,
            'shelf_id' => $shelfId,
        ]);

        $newQty = ($stock->exists ? $stock->quantity : 0) + $quantityChange;
        if ($newQty < 0) {
            throw new Exception(__('messages.stock_adjustment.insufficient_stock'));
        }

        $stock->quantity = $newQty;
        $stock->save();

        return $stock;
    }


    public static function createAdjustment(array $data): StockAdjustment
    {
        return StockAdjustment::create([
            'variant_id'    => $data['variant_id'],
            'warehouse_id'  => $data['warehouse_id'],
            'shelf_id'      => $data['shelf_id'] ?? null,
            'type'          => $data['type'],
            'quantity'      => $data['quantity'],
            'cost_per_item' => $data['cost_per_item'] ?? null,
            'reason'        => $data['reason'] ?? null,
            'adjusted_by'   => $data['adjusted_by'] ?? null,
            'reference_id'  => $data['reference_id'] ?? null,
            'reference_type' => $data['reference_type'] ?? null,
        ]);
    }

    public static function systemAdjust(array $data): StockAdjustment
    {
        // $data must include:
        // variant_id, warehouse_id, shelf_id (nullable), type (e.g. 'sale', 'return'),
        // quantity (positive integer), cost_per_item (nullable),
        // reason (nullable), reference_id, reference_type

        $typeMap = [
            'return' => 1,
            'sale' => -1,
            // Add more types here if needed: 'purchase' => 1, 'supplier_return' => -1, etc.
        ];

        if (!isset($typeMap[$data['type']])) {
            throw new InvalidArgumentException(__('messages.stock_adjustment.invalid_type'));
        }

        $quantityChange = $typeMap[$data['type']] * abs($data['quantity']);

        try {
            return DB::transaction(function () use ($data, $quantityChange) {
                // Update stock quantity
                self::updateStockQuantity(
                    $data['variant_id'],
                    $data['warehouse_id'],
                    $data['shelf_id'] ?? null,
                    $quantityChange
                );

                // Create adjustment record
                return self::createAdjustment(array_merge($data, [
                    'quantity' => $quantityChange,
                    'adjusted_by' => $data['adjusted_by'] ?? Auth::id(),
                ]));
            });
        } catch (Exception $e) {
            throw new Exception(__('messages.stock_adjustment.failed_to_adjust'), 500, $e);
        }
    }
}
