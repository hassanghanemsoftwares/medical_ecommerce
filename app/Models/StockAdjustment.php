<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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
        'parent_adjustment_id',
    ];

    // Relationships
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

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_adjustment_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_adjustment_id');
    }

    public function stock()
    {
        return Stock::where('variant_id', $this->variant_id)
            ->where('warehouse_id', $this->warehouse_id)
            ->where('shelf_id', $this->shelf_id)
            ->first();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('StockAdjustment')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
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
                'parent_adjustment_id',
            ]);
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }

    public static function updateStockQuantity(int $variantId, int $warehouseId, ?int $shelfId, int $quantityChange): Stock
    {
        $stock = Stock::firstOrNew([
            'variant_id'   => $variantId,
            'warehouse_id' => $warehouseId,
            'shelf_id'     => $shelfId,
        ]);

        $stock->quantity = max(0, ($stock->exists ? $stock->quantity : 0) + $quantityChange);

        if ($stock->quantity < 0) {
            throw new Exception(__('messages.stock_adjustment.insufficient_stock'));
        }

        $stock->save();
        return $stock;
    }

    public static function createAdjustment(array $data): self
    {
        return self::create([
            'variant_id'            => $data['variant_id'],
            'warehouse_id'          => $data['warehouse_id'],
            'shelf_id'              => $data['shelf_id'] ?? null,
            'type'                  => $data['type'],
            'quantity'              => $data['quantity'],
            'cost_per_item'         => $data['cost_per_item'] ?? null,
            'reason'                => $data['reason'] ?? null,
            'adjusted_by'           => $data['adjusted_by'] ?? Auth::id(),
            'reference_id'          => $data['reference_id'] ?? null,
            'reference_type'        => $data['reference_type'] ?? null,
            'parent_adjustment_id'  => $data['parent_adjustment_id'] ?? null,
        ]);
    }

    public static function systemAdjust(array $data): self
    {
        $typeMap = [
            'return' => 1,
            'sale'   => -1,
        ];

        if (!isset($typeMap[$data['type']])) {
            throw new InvalidArgumentException(__('messages.stock_adjustment.invalid_type'));
        }

        $quantityChange = $typeMap[$data['type']] * abs($data['quantity']);

        return DB::transaction(function () use ($data, $quantityChange) {
            self::updateStockQuantity(
                $data['variant_id'],
                $data['warehouse_id'],
                $data['shelf_id'] ?? null,
                $quantityChange
            );

            return self::createAdjustment(array_merge($data, [
                'quantity' => $quantityChange,
            ]));
        }, 3);
    }

    public static function deductForOrder(int $variantId, int $quantity, array $meta): void
    {
        $variant = Variant::findOrFail($variantId);
        $remaining = $quantity;

        // Load all adjustments of this variant once
        $allAdjustments = self::where('variant_id', $variantId)->get();

        // Positive adjustments = stock sources (manual and return)
        $positiveAdjustments = $allAdjustments
            ->whereIn('type', ['manual', 'return'])
            ->sortBy('created_at'); // FIFO: oldest stock first

        // Group sales by parent adjustment ID
        $salesGrouped = $allAdjustments
            ->where('type', 'sale')
            ->groupBy('parent_adjustment_id');

        foreach ($positiveAdjustments as $adjustment) {
            if ($remaining <= 0) break;

            // Calculate already deducted quantity (negative values)
            $alreadyDeducted = $salesGrouped
                ->get($adjustment->id, collect())
                ->sum('quantity'); // sum of negative sale adjustments

            // Calculate available quantity in this batch (adjustment quantity + already deducted sales)
            $available = $adjustment->quantity + $alreadyDeducted;

            if ($available <= 0) {
                // No stock left in this batch
                continue;
            }

            // Deduct as much as possible from this batch
            $deductQty = min($available, $remaining);

            // Create a sale adjustment to reduce stock
            self::systemAdjust([
                'variant_id'            => $variantId,
                'warehouse_id'          => $adjustment->warehouse_id,
                'shelf_id'              => $adjustment->shelf_id,
                'type'                  => 'sale',
                'quantity'              => -$deductQty,
                'cost_per_item'         => $adjustment->cost_per_item,
                'reason'                => $meta['reason'] ?? 'Order',
                'reference_id'          => $meta['reference_id'] ?? null,
                'reference_type'        => $meta['reference_type'] ?? null,
                'parent_adjustment_id'  => $adjustment->id,
            ]);

            $remaining -= $deductQty;
        }

        if ($remaining > 0) {
            // Not enough stock available across all batches
            throw new \Exception(__('messages.order.insufficient_stock', [
                'sku'       => $variant->display_sku,
                'available' => $quantity - $remaining,
            ]));
        }
    }
}
