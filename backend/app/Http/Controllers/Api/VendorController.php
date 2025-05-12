<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VendorController extends Controller
{
    /**
     * Afficher le tableau de bord du vendeur
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        
        // Vérifier que l'utilisateur est un vendeur
        if ($user->role !== 'vendeur') {
            return response()->json([
                'message' => 'Accès non autorisé'
            ], 403);
        }
        
        // Statistiques des produits
        $totalProducts = Product::where('user_id', $user->id)->count();
        $activeProducts = Product::where('user_id', $user->id)->where('active', true)->count();
        $lowStockProducts = Product::where('user_id', $user->id)
            ->whereRaw('stock <= stock_threshold')
            ->count();
        
        // Produits récemment ajoutés
        $recentProducts = Product::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        // Trouver les commandes contenant les produits du vendeur
        $productIds = Product::where('user_id', $user->id)->pluck('id')->toArray();
        
        $orderIds = DB::table('order_items')
            ->whereIn('product_id', $productIds)
            ->pluck('order_id')
            ->unique()
            ->toArray();
            
        $totalOrders = count($orderIds);
        
        // Commandes récentes
        $recentOrders = Order::whereIn('id', $orderIds)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->with(['items' => function ($query) use ($productIds) {
                $query->whereIn('product_id', $productIds);
            }, 'user'])
            ->get();
            
        // Ventes par jour sur les 7 derniers jours
        $salesByDay = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('order_items.product_id', $productIds)
            ->where('orders.status', 'completed')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(7))
            ->select(
                DB::raw('DATE(orders.created_at) as date'),
                DB::raw('SUM(order_items.price * order_items.quantity) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        // Calculer le revenu total
        $totalRevenue = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('order_items.product_id', $productIds)
            ->whereIn('orders.status', ['completed', 'delivered'])
            ->sum(DB::raw('order_items.price * order_items.quantity'));
            
        // Produits les plus vendus
        $topProducts = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereIn('order_items.product_id', $productIds)
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.price * order_items.quantity) as total_revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();
            
        return response()->json([
            'stats' => [
                'total_products' => $totalProducts,
                'active_products' => $activeProducts,
                'low_stock_products' => $lowStockProducts,
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue
            ],
            'recent_products' => $recentProducts,
            'recent_orders' => $recentOrders,
            'sales_by_day' => $salesByDay,
            'top_products' => $topProducts
        ]);
    }
    
    /**
     * Afficher le profil du vendeur
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        
        // Vérifier que l'utilisateur est un vendeur
        if ($user->role !== 'vendeur') {
            return response()->json([
                'message' => 'Accès non autorisé'
            ], 403);
        }
        
        return response()->json([
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'store_name' => $user->store_name,
                'store_description' => $user->store_description,
                'store_logo' => $user->avatar,
                'store_banner' => $user->store_banner,
                'payment_methods' => $user->payment_methods,
                'shipping_methods' => $user->shipping_methods,
                'return_policy' => $user->return_policy,
                'joined_at' => $user->created_at,
                'rating' => $this->getVendorRating($user->id)
            ]
        ]);
    }
    
    /**
     * Mettre à jour le profil du vendeur
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        // Vérifier que l'utilisateur est un vendeur
        if ($user->role !== 'vendeur') {
            return response()->json([
                'message' => 'Accès non autorisé'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string|max:255',
            'avatar' => 'nullable|image|max:2048',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        // Mettre à jour les informations générales
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        
        if ($request->has('phone')) {
            $user->phone = $request->phone;
        }
        
        if ($request->has('address')) {
            $user->address = $request->address;
        }
        
        // Traitement de l'avatar
        if ($request->hasFile('avatar')) {
            // Supprimer l'ancien avatar si existant
            if ($user->avatar && Storage::exists(str_replace('/storage/', 'public/', $user->avatar))) {
                Storage::delete(str_replace('/storage/', 'public/', $user->avatar));
            }
            
            $avatar = $request->file('avatar');
            $avatarName = time() . '.' . $avatar->getClientOriginalExtension();
            $path = $avatar->storeAs('avatars', $avatarName, 'public');
            $user->avatar = '/storage/' . $path;
        }
        
        $user->save();
        
        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'avatar' => $user->avatar
            ]
        ]);
    }
    
    /**
     * Mettre à jour les paramètres de la boutique du vendeur
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStoreSettings(Request $request)
    {
        $user = $request->user();
        
        // Vérifier que l'utilisateur est un vendeur
        if ($user->role !== 'vendeur') {
            return response()->json([
                'message' => 'Accès non autorisé'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'store_name' => 'sometimes|required|string|max:255',
            'store_description' => 'sometimes|string|max:1000',
            'store_banner' => 'nullable|image|max:2048',
            'payment_methods' => 'sometimes|array',
            'shipping_methods' => 'sometimes|array',
            'return_policy' => 'sometimes|string|max:2000',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        // Mettre à jour les paramètres de la boutique
        if ($request->has('store_name')) {
            $user->store_name = $request->store_name;
        }
        
        if ($request->has('store_description')) {
            $user->store_description = $request->store_description;
        }
        
        if ($request->has('payment_methods')) {
            $user->payment_methods = $request->payment_methods;
        }
        
        if ($request->has('shipping_methods')) {
            $user->shipping_methods = $request->shipping_methods;
        }
        
        if ($request->has('return_policy')) {
            $user->return_policy = $request->return_policy;
        }
        
        // Gestion de la bannière de la boutique
        if ($request->hasFile('store_banner')) {
            // Supprimer l'ancienne bannière si existante
            if ($user->store_banner && Storage::exists(str_replace('/storage/', 'public/', $user->store_banner))) {
                Storage::delete(str_replace('/storage/', 'public/', $user->store_banner));
            }
            
            $banner = $request->file('store_banner');
            $bannerName = time() . '.' . $banner->getClientOriginalExtension();
            $path = $banner->storeAs('store_banners', $bannerName, 'public');
            $user->store_banner = '/storage/' . $path;
        }
        
        $user->save();
        
        return response()->json([
            'message' => 'Paramètres de la boutique mis à jour avec succès',
            'settings' => [
                'store_name' => $user->store_name,
                'store_description' => $user->store_description,
                'store_logo' => $user->avatar,
                'store_banner' => $user->store_banner,
                'payment_methods' => $user->payment_methods,
                'shipping_methods' => $user->shipping_methods,
                'return_policy' => $user->return_policy,
                'is_featured' => $user->is_featured,
            ]
        ]);
    }
    
    /**
     * Liste les commandes du vendeur
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function orders(Request $request)
    {
        $user = $request->user();
        
        // Vérifier que l'utilisateur est un vendeur
        if ($user->role !== 'vendeur') {
            return response()->json([
                'message' => 'Accès non autorisé'
            ], 403);
        }
        
        // Trouver les commandes contenant les produits du vendeur
        $productIds = Product::where('user_id', $user->id)->pluck('id')->toArray();
        
        $query = Order::whereHas('items', function ($query) use ($productIds) {
            $query->whereIn('product_id', $productIds);
        })->with(['user', 'items' => function ($query) use ($productIds) {
            $query->whereIn('product_id', $productIds)->with('product');
        }]);
        
        // Filtrage par statut
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filtrage par date
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Tri
        $sortField = $request->input('sort', 'created_at');
        $sortOrder = $request->input('order', 'desc');
        $query->orderBy($sortField, $sortOrder);
        
        // Pagination
        $perPage = $request->input('per_page', 10);
        $orders = $query->paginate($perPage);
        
        return response()->json([
            'orders' => $orders,
            'pagination' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage()
            ]
        ]);
    }
    
    /**
     * Affiche le détail d'une commande du vendeur
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function orderDetail(Request $request, $id)
    {
        $user = $request->user();
        
        // Vérifier que l'utilisateur est un vendeur
        if ($user->role !== 'vendeur') {
            return response()->json([
                'message' => 'Accès non autorisé'
            ], 403);
        }
        
        $productIds = Product::where('user_id', $user->id)->pluck('id')->toArray();
        
        // Vérifier si la commande contient des produits du vendeur
        $order = Order::whereHas('items', function ($query) use ($productIds) {
            $query->whereIn('product_id', $productIds);
        })
        ->with(['user', 'items' => function ($query) use ($productIds) {
            $query->whereIn('product_id', $productIds)->with('product');
        }, 'payment'])
        ->findOrFail($id);
        
        return response()->json([
            'order' => $order
        ]);
    }
    
    /**
     * Mettre à jour le statut de traitement des produits du vendeur dans une commande
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.status' => 'required|string|in:pending,processing,shipped,delivered',
            'items.*.tracking_number' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        $user = $request->user();
        
        // Vérifier que l'utilisateur est un vendeur
        if ($user->role !== 'vendeur') {
            return response()->json([
                'message' => 'Accès non autorisé'
            ], 403);
        }
        
        $productIds = Product::where('user_id', $user->id)->pluck('id')->toArray();
        
        // Vérifier si la commande existe et contient des produits du vendeur
        $order = Order::whereHas('items', function ($query) use ($productIds) {
            $query->whereIn('product_id', $productIds);
        })->findOrFail($id);
        
        // Mettre à jour le statut des éléments de commande
        foreach ($request->items as $item) {
            $orderItem = DB::table('order_items')
                ->where('id', $item['order_item_id'])
                ->whereIn('product_id', $productIds)
                ->first();
                
            if (!$orderItem) {
                return response()->json([
                    'message' => 'Element de commande non trouvé ou n\'appartient pas au vendeur',
                    'order_item_id' => $item['order_item_id']
                ], 404);
            }
            
            DB::table('order_items')
                ->where('id', $item['order_item_id'])
                ->update([
                    'status' => $item['status'],
                    'tracking_number' => $item['tracking_number'] ?? null,
                    'updated_at' => now()
                ]);
        }
        
        // Récupérer les éléments mis à jour
        $updatedItems = DB::table('order_items')
            ->where('order_id', $id)
            ->whereIn('product_id', $productIds)
            ->get();
            
        return response()->json([
            'message' => 'Statut des produits mis à jour avec succès',
            'items' => $updatedItems
        ]);
    }
    
    /**
     * Récupérer la note moyenne d'un vendeur
     *
     * @param int $vendorId
     * @return float
     */
    private function getVendorRating($vendorId)
    {
        // Cette fonction simule le calcul de la note moyenne des produits d'un vendeur
        // Dans une implémentation réelle, vous récupéreriez les avis des produits du vendeur
        
        $products = Product::where('user_id', $vendorId)->pluck('id')->toArray();
        
        if (empty($products)) {
            return 0;
        }
        
        // Simuler un calcul de note moyenne
        // Remplacez ceci par une requête réelle vers votre table d'avis
        $rating = rand(35, 50) / 10; // Rating entre 3.5 et 5.0
        
        return $rating;
    }
}