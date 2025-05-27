<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

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
}
