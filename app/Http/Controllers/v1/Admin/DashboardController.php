<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Client;
use App\Models\ClientSession;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $today = Carbon::today();
            $startMonth = Carbon::now()->startOfMonth();
            $last30Days = Carbon::now()->subDays(30);
            $lastMonth = Carbon::now()->subMonth();

            // KPIs
            $totalRevenue = Order::whereIn('status', [10])
                ->where('is_cart', false)
                ->where('is_preorder', false)
                ->get()
                ->sum(fn($order) => $order->grand_total);

            $monthlyRevenue = Order::where('created_at', '>=', $startMonth)
                ->where('is_cart', false)
                ->where('is_preorder', false)
                ->get()
                ->sum(fn($order) => $order->grand_total);

            $prevRevenue = Order::whereMonth('created_at', $lastMonth->month)
                ->where('is_cart', false)
                ->where('is_preorder', false)
                ->get()
                ->sum(fn($order) => $order->grand_total);

            $totalClients = Client::count();
            $activeClients = Client::where('is_active', true)->count();
            $conversionRate = $totalClients > 0 ? round(($activeClients / $totalClients) * 100, 2) : 0;

            // Time-series (Revenue over last 30 days)
            $revenueByDay = Order::where('created_at', '>=', $last30Days)
                ->where('is_cart', false)
                ->where('is_preorder', false)
                ->get()
                ->groupBy(fn($order) => $order->created_at->format('Y-m-d'))
                ->map(function ($orders, $date) {
                    return [
                        'date' => $date,
                        'total' => $orders->sum(fn($order) => $order->grand_total),
                    ];
                })
                ->values();

            // Bar chart: Top 5 products by order count
            $topProducts = Product::select('products.id', 'products.name', DB::raw('COUNT(order_details.id) as orders_count'))
                ->join('variants', 'variants.product_id', '=', 'products.id')
                ->join('order_details', 'order_details.variant_id', '=', 'variants.id')
                ->join('orders', 'orders.id', '=', 'order_details.order_id')
                ->where('orders.is_cart', false)
                ->where('orders.is_preorder', false)
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('orders_count')
                ->take(5)
                ->get();
            $topProducts = $topProducts->map(function ($product) {
                $productModel = Product::find($product->id);
                return [
                    'name' => $productModel?->getTranslation('name', app()->getLocale()) ?? 'Unknown',
                    'orders_count' => $product->orders_count,
                ];
            });

            // Pie: Sales by category
            $salesByCategory = DB::table('order_details')
                ->join('variants', 'order_details.variant_id', '=', 'variants.id')
                ->join('products', 'variants.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->join('orders', 'orders.id', '=', 'order_details.order_id')
                ->where('orders.is_cart', false)
                ->where('orders.is_preorder', false)
                ->select('categories.id as category_id', DB::raw('SUM(order_details.price * order_details.quantity) as total'))
                ->groupBy('categories.id')
                ->get();

            $salesByCategory = $salesByCategory->map(function ($categoryData) {
                $category = Category::find($categoryData->category_id);
                return [
                    'product_category' => $category?->getTranslation('name', app()->getLocale()) ?? 'Unknown',
                    'total' => $categoryData->total,
                ];
            });

            // Geographic: Clients by country
            $clientsByCountry = DB::table('clients')
                ->join('addresses', 'clients.id', '=', 'addresses.client_id')
                ->select('addresses.country', DB::raw('COUNT(clients.id) as count'))
                ->groupBy('addresses.country')
                ->get();

            // Funnel: Client sessions (visits), registered clients (leads), and active clients (conversions)
            $leadFunnel = [
                'visits' => ClientSession::count(),
                'leads' => $totalClients,
                'conversions' => $activeClients,
            ];

            $growthRate = $prevRevenue > 0
                ? round((($monthlyRevenue - $prevRevenue) / $prevRevenue) * 100, 2)
                : 0;

            return response()->json([
                'result' => true,
                'message' => __('messages.dashboard.dashboard_retrieved'),
                'kpis' => [
                    'totalRevenue' => $totalRevenue,
                    'monthlyRevenue' => $monthlyRevenue,
                    'activeClients' => $activeClients,
                    'conversionRate' => $conversionRate,
                    'growthRate' => $growthRate,
                ],
                'charts' => [
                    'revenueByDay' => $revenueByDay,
                    'topProducts' => $topProducts,
                    'salesByCategory' => $salesByCategory,
                    'clientsByCountry' => $clientsByCountry,
                    'leadFunnel' => $leadFunnel,
                ]
            ]);
            // return response()->json([
            //     "result" => true,
            //     "message" => "Dashboard data retrieved successfully",
            //     "kpis" => [
            //         "totalRevenue" => 125000,
            //         "monthlyRevenue" => 25000,
            //         "activeClients" => 150,
            //         "conversionRate" => 75.5,
            //         "growthRate" => 12.5
            //     ],
            //     "charts" => [
            //         "revenueByDay" => [
            //             ["date" => "2023-05-01", "total" => 1200],
            //             ["date" => "2023-05-02", "total" => 1800],
            //             ["date" => "2023-05-03", "total" => 1500],
            //             ["date" => "2023-05-04", "total" => 2100],
            //             ["date" => "2023-05-05", "total" => 1900],
            //             ["date" => "2023-05-06", "total" => 2300],
            //             ["date" => "2023-05-07", "total" => 1700],
            //             ["date" => "2023-05-08", "total" => 2000],
            //             ["date" => "2023-05-09", "total" => 2400],
            //             ["date" => "2023-05-10", "total" => 2600],
            //             ["date" => "2023-05-11", "total" => 2200],
            //             ["date" => "2023-05-12", "total" => 1900],
            //             ["date" => "2023-05-13", "total" => 2100],
            //             ["date" => "2023-05-14", "total" => 2300],
            //             ["date" => "2023-05-15", "total" => 2500],
            //             ["date" => "2023-05-16", "total" => 2700],
            //             ["date" => "2023-05-17", "total" => 2400],
            //             ["date" => "2023-05-18", "total" => 2200],
            //             ["date" => "2023-05-19", "total" => 2000],
            //             ["date" => "2023-05-20", "total" => 1800],
            //             ["date" => "2023-05-21", "total" => 2100],
            //             ["date" => "2023-05-22", "total" => 2300],
            //             ["date" => "2023-05-23", "total" => 2500],
            //             ["date" => "2023-05-24", "total" => 2700],
            //             ["date" => "2023-05-25", "total" => 2900],
            //             ["date" => "2023-05-26", "total" => 2600],
            //             ["date" => "2023-05-27", "total" => 2400],
            //             ["date" => "2023-05-28", "total" => 2200],
            //             ["date" => "2023-05-29", "total" => 2000],
            //             ["date" => "2023-05-30", "total" => 2300]
            //         ],
            //         "topProducts" => [
            //             ["id" => 1, "name" => "Product A", "orders_count" => 45],
            //             ["id" => 2, "name" => "Product B", "orders_count" => 32],
            //             ["id" => 3, "name" => "Product C", "orders_count" => 28],
            //             ["id" => 4, "name" => "Product D", "orders_count" => 22],
            //             ["id" => 5, "name" => "Product E", "orders_count" => 18]
            //         ],
            //         "salesByCategory" => [
            //             ["product_category" => "Electronics", "total" => 45000],
            //             ["product_category" => "Clothing", "total" => 30000],
            //             ["product_category" => "Home & Garden", "total" => 25000],
            //             ["product_category" => "Sports", "total" => 15000],
            //             ["product_category" => "Beauty", "total" => 10000]
            //         ],
            //         "clientsByCountry" => [
            //             ["country" => "USA", "count" => 75],
            //             ["country" => "UK", "count" => 45],
            //             ["country" => "Canada", "count" => 30],
            //             ["country" => "Australia", "count" => 25],
            //             ["country" => "Germany", "count" => 20]
            //         ],
            //         "leadFunnel" => [
            //             "visits" => 1000,
            //             "leads" => 200,
            //             "conversions" => 150
            //         ]
            //     ]
            // ]);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.dashboard.failed_to_retrieve'), $e);
        }
    }
}
