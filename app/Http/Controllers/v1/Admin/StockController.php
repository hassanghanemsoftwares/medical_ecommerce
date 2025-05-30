<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PaginationResource;
use App\Http\Resources\V1\StockResource;
use App\Models\Stock;
use Illuminate\Http\Request;
use Exception;

class StockController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search'   => 'nullable|string|max:255',
                'sort'     => 'nullable|in:quantity,created_at,updated_at,warehouse_name,shelf_name',
                'order'    => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = Stock::with([
                'variant.product',
                'variant.color',
                'variant.size',
                'warehouse',
                'shelf'
            ]);

            if (!empty($validated['search'])) {
                $searchTerm = $validated['search'];
                $query->whereHas('variant', function ($q) use ($searchTerm) {
                    $q->where('sku', 'like', "%{$searchTerm}%");
                })->orWhereHas('warehouse', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%");
                });
            }

            $sort = $validated['sort'] ?? 'created_at';
            $order = $validated['order'] ?? 'desc';

            // Handle sorting by related model fields:
            if ($sort === 'warehouse_name') {
                $query->leftJoin('warehouses', 'stocks.warehouse_id', '=', 'warehouses.id')
                    ->orderBy('warehouses.name', $order)
                    ->select('stocks.*'); // avoid ambiguous columns
            } elseif ($sort === 'shelf_name') {
                $query->leftJoin('shelves', 'stocks.shelf_id', '=', 'shelves.id')
                    ->orderBy('shelves.name', $order)
                    ->select('stocks.*');
            } else {
                $query->orderBy($sort, $order);
            }

            $stocks = $query->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result' => true,
                'message' => __('messages.stocks.retrieved'),
                'stocks' => StockResource::collection($stocks),
                'pagination' => new PaginationResource($stocks),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'result' => false,
                'message' => __('messages.stocks.failed_to_retrieve_data'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
