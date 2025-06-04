<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Admin\AddressResource;
use App\Http\Resources\V1\Admin\BrandResource;
use App\Http\Resources\V1\Admin\CategoryResource;
use App\Http\Resources\V1\Admin\ClientResource;
use App\Http\Resources\V1\Admin\ColorResource;
use App\Http\Resources\V1\Admin\ColorSeasonResource;
use App\Http\Resources\V1\Admin\ConfigurationResource;
use App\Http\Resources\V1\Admin\OccupationResource;
use App\Http\Resources\V1\Admin\OrderResource;
use App\Http\Resources\V1\Admin\ProductsVariantsResource;
use App\Http\Resources\V1\Admin\ShelfResource;
use App\Http\Resources\V1\Admin\SizeResource;
use App\Http\Resources\V1\Admin\TagResource;
use App\Http\Resources\V1\Admin\WarehouseResource;
use App\Models\Address;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Client;
use App\Models\Color;
use App\Models\ColorSeason;
use App\Models\Configuration;
use App\Models\Contact;
use App\Models\Occupation;
use App\Models\Order;
use App\Models\ReturnOrder;
use App\Models\Shelf;
use App\Models\Size;
use App\Models\Tag;
use App\Models\Variant;
use App\Models\Warehouse;
use Exception;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SettingsController extends Controller
{

    public function index(): JsonResponse
    {
        try {
            $permissions = Permission::select('id', 'name')
                ->whereNotIn('name', ['view-activity-logs'])
                ->orderBy('id', 'asc')
                ->get();

            $roles = Role::where('team_id', getPermissionsTeamId())
                ->select('id', 'name')
                ->get();

            return response()->json([
                'result' => true,
                'message' => __('messages.retrieved_successfully'),
                'permissions' => $permissions,
                'roles' => $roles,
                'categories' => CategoryResource::collection(Category::all()),
                'brands' => BrandResource::collection(Brand::all()),
                'color_seasons' => ColorSeasonResource::collection(ColorSeason::all()),
                'colors' => ColorResource::collection(Color::with('colorSeason')->get()),
                'configurations' => ConfigurationResource::collection(Configuration::all()),
                'occupations' => OccupationResource::collection(Occupation::all()),
                'shelves' => ShelfResource::collection(Shelf::with('warehouse')->get()),
                'sizes' => SizeResource::collection(Size::all()),
                'tags' => TagResource::collection(Tag::all()),
                'warehouses' => WarehouseResource::collection(Warehouse::all()),
            ]);
        } catch (Exception $e) {

            return $this->errorResponse('messages.failed_to_retrieve_data', $e);
        }
    }
    public function getAllClients(Request $request)
    {
        try {
            $perPage = $request->input('limit', 10);
            $page = $request->input('page', 1);
            $searchTerm = $request->input('search', '');

            $query = Client::with('occupation');

            if ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhere('phone', 'like', "%{$searchTerm}%");
                });
            }

            $clients = $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'result' => true,
                'message' => __('messages.client.clients_retrieved'),
                'clients' => ClientResource::collection($clients),
                'total' => $clients->total(),
                'page' => $clients->currentPage(),
                'limit' => $clients->perPage(),
                'last_page' => $clients->lastPage(),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('messages.client.failed_to_retrieve_data', $e);
        }
    }
    public function getAllProductsVariants(Request $request)
    {
        try {
            $perPage = $request->input('limit', 20);
            $page = $request->input('page', 1);
            $searchTerm = $request->input('search', '');

            $query = Variant::with([
                'product',
                'product.category',
                'size',
                'color',
            ]);

            if ($searchTerm) {
                $locale = app()->getLocale();

                $query->where(function ($q) use ($searchTerm, $locale) {
                    $q->where('sku', 'like', '%' . $searchTerm . '%');
                    $q->orWhereHas('product', function ($productQuery) use ($searchTerm, $locale) {
                        $productQuery->where('barcode', 'like', '%' . $searchTerm . '%')
                            ->orWhere("name->{$locale}", 'like', '%' . $searchTerm . '%');
                    });
                    $q->orWhereHas('product.category', function ($categoryQuery) use ($searchTerm, $locale) {
                        $categoryQuery->where("name->{$locale}", 'like', '%' . $searchTerm . '%');
                    });
                });
            }

            $variants = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'result' => true,
                'message' => __('messages.product.products_retrieved'),
                'productVariants' => ProductsVariantsResource::collection($variants),
                'total' => $variants->total(),
                'page' => $variants->currentPage(),
                'limit' => $variants->perPage(),
                'last_page' => $variants->lastPage(),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('messages.product.failed_to_retrieve_data', $e);
        }
    }

    public function getOrderableVariants(Request $request)
    {
        try {
            $perPage = $request->input('limit', 20);
            $page = $request->input('page', 1);
            $searchTerm = $request->input('search', '');

            $query = Variant::with([
                'product',
                'product.category',
                'size',
                'color',
            ])
                ->whereHas('product', function ($q) {
                    $q->whereIn('availability_status', ['available', 'pre_order']);
                })
                ->whereHas('stocks', function ($q) {})
                ->withSum('stocks as total_stock_quantity', 'quantity')
                ->having('total_stock_quantity', '>', 0);

            if ($searchTerm) {
                $locale = app()->getLocale();

                $query->where(function ($q) use ($searchTerm, $locale) {
                    $q->where('sku', 'like', '%' . $searchTerm . '%')
                        ->orWhereHas('product', function ($productQuery) use ($searchTerm, $locale) {
                            $productQuery->where('barcode', 'like', '%' . $searchTerm . '%')
                                ->orWhere("name->{$locale}", 'like', '%' . $searchTerm . '%');
                        })
                        ->orWhereHas('product.category', function ($categoryQuery) use ($searchTerm, $locale) {
                            $categoryQuery->where("name->{$locale}", 'like', '%' . $searchTerm . '%');
                        });
                });
            }

            $variants = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'result' => true,
                'message' => __('messages.product.orderable_variants_retrieved'),
                'productVariants' => ProductsVariantsResource::collection($variants),
                'total' => $variants->total(),
                'page' => $variants->currentPage(),
                'limit' => $variants->perPage(),
                'last_page' => $variants->lastPage(),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('messages.product.failed_to_retrieve_data', $e);
        }
    }

    public function getClientAddresses(Request $request)
    {
        try {
            $perPage = $request->input('limit', 20);
            $page = $request->input('page', 1);
            $searchTerm = $request->input('search', '');
            $clientId = $request->input('client_id');

            if (!$clientId || !Client::where('id', $clientId)->exists()) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.addresses.invalid_client_id'),
                ], 422);
            }

            $query = Address::with('client')
                ->where('client_id', $clientId);

            if ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('country', 'like', "%$searchTerm%")
                        ->orWhere('city', 'like', "%$searchTerm%")
                        ->orWhere('district', 'like', "%$searchTerm%")
                        ->orWhere('governorate', 'like', "%$searchTerm%");
                });
            }

            $addresses = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'result' => true,
                'message' => __('messages.addresses.addresses_retrieved'),
                'addresses' => AddressResource::collection($addresses),
                'total' => $addresses->total(),
                'page' => $addresses->currentPage(),
                'limit' => $addresses->perPage(),
                'last_page' => $addresses->lastPage(),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('messages.addresses.failed_to_retrieve_data', $e);
        }
    }

    public function getOrdersCanBeReturned(Request $request)
    {
        try {
            $perPage = $request->input('limit', 20);
            $page = $request->input('page', 1);
            $searchTerm = $request->input('search', '');

            $query = Order::query()
                ->with([
                    'client:id,name',
                    'orderDetails.variant:id,product_id,color_id,size_id',
                    'orderDetails.variant.product:id,name',
                    'orderDetails.variant.color:id,name',
                    'orderDetails.variant.size:id,name',
                    'returnOrders.details'
                ])
                ->whereIn('status', [5, 9, 10]);

            if ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('order_number', 'like', "%$searchTerm%")
                        ->orWhereHas('client', fn($client) =>
                        $client->where('name', 'like', "%$searchTerm%"));
                });
            }

            $orders = $query->latest()->get();

            $filtered = $orders->map(function ($order) {
                $returnedQtyMap = $order->returnedQuantities();

                $details = $order->orderDetails->map(function ($detail) use ($returnedQtyMap) {
                    $orderedQty = $detail->quantity;
                    $variantId = $detail->variant_id;
                    $returnedQty = $returnedQtyMap[$variantId] ?? 0;

                    $returnableQty = $orderedQty - $returnedQty;

                    if ($returnableQty > 0) {
                        $detail->quantity = $returnableQty;
                        return $detail;
                    }

                    return null;
                })->filter();

                $order->setRelation('orderDetails', $details);
                return $details->isNotEmpty() ? $order : null;
            })->filter()->values();

            $paginated = new LengthAwarePaginator(
                $filtered->forPage($page, $perPage),
                $filtered->count(),
                $perPage,
                $page
            );

            return response()->json([
                'result' => true,
                'message' => __('messages.order.retrieved_successfully'),
                'orders' => OrderResource::collection($paginated),
                'total' => $paginated->total(),
                'page' => $paginated->currentPage(),
                'limit' => $paginated->perPage(),
                'last_page' => $paginated->lastPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'result' => false,
                'message' => __('messages.order.failed_to_retrieve'),
            ]);
        }
    }

    public function getAllProductsVariantsCanBePreOrder(Request $request)
    {
        try {
            $perPage = $request->input('limit', 20);
            $page = $request->input('page', 1);
            $searchTerm = $request->input('search', '');

            $locale = app()->getLocale();

            $query = Variant::with([
                'product',
                'product.category',
                'size',
                'color',
            ])
                ->whereHas('product', function ($q) {
                    $q->where('availability_status', 'pre_order');
                })
                ->whereDoesntHave('stocks', function ($q) {
                    $q->where('quantity', '>', 0);
                });

            if ($searchTerm) {
                $query->where(function ($q) use ($searchTerm, $locale) {
                    $q->where('sku', 'like', '%' . $searchTerm . '%')
                        ->orWhereHas('product', function ($productQuery) use ($searchTerm, $locale) {
                            $productQuery->where('barcode', 'like', '%' . $searchTerm . '%')
                                ->orWhere("name->{$locale}", 'like', '%' . $searchTerm . '%');
                        })
                        ->orWhereHas('product.category', function ($categoryQuery) use ($searchTerm, $locale) {
                            $categoryQuery->where("name->{$locale}", 'like', '%' . $searchTerm . '%');
                        });
                });
            }

            $variants = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'result' => true,
                'message' => __('messages.product.products_retrieved'),
                'productVariants' => ProductsVariantsResource::collection($variants),
                'total' => $variants->total(),
                'page' => $variants->currentPage(),
                'limit' => $variants->perPage(),
                'last_page' => $variants->lastPage(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('messages.product.failed_to_retrieve_data', $e);
        }
    }

    public function getNotifications()
    {
        try {
            // Regular unread orders (non-preorder)
            $unreadOrders = Order::where('is_view', false)
                ->where('is_preorder', false)
                ->with('client')
                ->latest()
                ->get();

            // Unread preorders
            $unreadPreorders = Order::where('is_view', false)
                ->where('is_preorder', true)
                ->with('client')
                ->latest()
                ->get();

            // Pending return orders
            $returnOrders = ReturnOrder::where('status', 0)
                ->with(['client', 'order'])
                ->latest()
                ->get();

            // Unread contact messages
            $unreadContacts = Contact::where('is_view', false)
                ->latest()
                ->get();

            // Map regular orders to notifications
            $orderNotifications = $unreadOrders->map(function ($order) {
                return [
                    'type' => 'order',
                    'order_id' => $order->id,
                    'message' => __('messages.notiifications.new_order_msg', ['name' => $order->client->name]),
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                ];
            });

            // Map preorders to notifications
            $preorderNotifications = $unreadPreorders->map(function ($order) {
                return [
                    'type' => 'preorder',
                    'order_id' => $order->id,
                    'message' => __('messages.notiifications.new_preorder_msg', ['name' => $order->client->name]),
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                ];
            });

            // Map return orders to notifications
            $returnNotifications = $returnOrders->map(function ($return) {
                return [
                    'type' => 'return_order',
                    'order_id' => $return->order_id,
                    'message' => __('messages.notiifications.return_order_msg', [
                        'name' => $return->client->name,
                        'order_number' => $return->order_number,
                    ]),
                    'created_at' => $return->requested_at->format('Y-m-d H:i:s'),
                ];
            });

            // Map contacts to notifications
            $contactNotifications = $unreadContacts->map(function ($contact) {
                return [
                    'type' => 'contact',
                    'contact_id' => $contact->id,
                    'message' => __('messages.notiifications.new_contact_msg', [
                        'name' => $contact->name,
                        'subject' => $contact->subject,
                    ]),
                    'created_at' => $contact->created_at->format('Y-m-d H:i:s'),
                ];
            });

            // Merge all notifications and sort by creation date descending
            $notifications = collect()
                ->merge($orderNotifications)
                ->merge($preorderNotifications)
                ->merge($returnNotifications)
                ->merge($contactNotifications)
                ->sortByDesc('created_at')
                ->values();

            return response()->json([
                'result' => true,
                'notifications' => $notifications,
                'unread_order_count' => $unreadOrders->count(),
                'unread_preorder_count' => $unreadPreorders->count(),
                'pending_return_count' => $returnOrders->count(),
                'unread_contact_count' => $unreadContacts->count(),
                'total_unread_count' => $unreadOrders->count() + $unreadPreorders->count() + $returnOrders->count() + $unreadContacts->count(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('messages.notiifications.failed_to_retrieve_notifications', $e);
        }
    }
}
