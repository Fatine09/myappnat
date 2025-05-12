<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatController extends Controller
{
    /**
     * Obtenir les statistiques pour le vendeur connecté
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sellerStats(Request $request)
    {
        $user = $request->user();
        
        // Statistiques des produits
        $totalProducts = Product::where('user_id', $user->id)->count();
        $activeProducts = Product::where('user_id', $user->id)->where('active', true)->count();
        $lowStockProducts = Product::where('user_id', $user->id)
            ->whereRaw('stock <= stock_threshold')
            ->count();
        
        // Statistiques des ventes (ordres contenant au moins un produit du vendeur)
        $productIds = Product::where('user_id', $user->id)->pluck('id');
        
        $orderItems = DB::table('order_items')
            ->whereIn('product_id', $productIds)
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select('orders.id')
            ->distinct()
            ->get();
            
        $orderIds = $orderItems->pluck('id');
        
        $totalOrders = count($orderIds);
        
        // Ventes par jour sur les 7 derniers jours
        $salesByDay = Payment::whereIn('order_id', $orderIds)
            ->where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Produits les plus vendus du vendeur
        $topProducts = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereIn('products.id', $productIds)
            ->select('products.id', 'products.name', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();
        
        return response()->json([
            'totalProducts' => $totalProducts,
            'activeProducts' => $activeProducts,
            'lowStockProducts' => $lowStockProducts,
            'totalOrders' => $totalOrders,
            'salesByDay' => $salesByDay,
            'topProducts' => $topProducts,
        ]);
    }
    
    /**
     * Obtenir les statistiques pour l'administrateur
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminStats(Request $request)
    {
        // Statistiques générales
        $totalUsers = User::count();
        $totalSellers = User::where('role', 'seller')->count();
        $totalCustomers = User::where('role', 'customer')->count();
        
        // Statistiques des produits
        $totalProducts = Product::count();
        $totalActiveProducts = Product::where('active', true)->count();
        $lowStockProducts = Product::whereRaw('stock <= stock_threshold')->count();
        
        // Statistiques des commandes
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $processingOrders = Order::where('status', 'processing')->count();
        $shippedOrders = Order::where('status', 'shipped')->count();
        $deliveredOrders = Order::where('status', 'delivered')->count();
        $cancelledOrders = Order::where('status', 'cancelled')->count();
        
        // Chiffre d'affaires
        $totalRevenue = Payment::where('status', 'completed')
            ->where('amount', '>', 0)  // Exclure les remboursements
            ->sum('amount');
            
        // Remboursements
        $totalRefunds = Payment::where('status', 'completed')
            ->where('amount', '<', 0)  // Seulement les remboursements
            ->sum('amount');
            
        // Revenus par mois des 6 derniers mois
        $revenueByMonth = Payment::where('status', 'completed')
            ->where('amount', '>', 0)
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
            
        // Top 5 des produits les plus vendus
        $topProducts = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select('products.id', 'products.name', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();
            
        // Top 5 des vendeurs par chiffre d'affaires
        $topSellers = DB::table('products')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('users', 'products.user_id', '=', 'users.id')
            ->where('users.role', 'seller')
            ->select(
                'users.id',
                'users.name',
                DB::raw('SUM(order_items.price * order_items.quantity) as total_sales')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get();
            
        // Statistiques des retours
        $totalReturns = ReturnRequest::count();
        $pendingReturns = ReturnRequest::where('status', 'pending')->count();
        $approvedReturns = ReturnRequest::where('status', 'approved')->count();
        $rejectedReturns = ReturnRequest::where('status', 'rejected')->count();
        $completedReturns = ReturnRequest::where('status', 'completed')->count();
        
        return response()->json([
            'users' => [
                'total' => $totalUsers,
                'sellers' => $totalSellers,
                'customers' => $totalCustomers
            ],
            'products' => [
                'total' => $totalProducts,
                'active' => $totalActiveProducts,
                'lowStock' => $lowStockProducts
            ],
            'orders' => [
                'total' => $totalOrders,
                'pending' => $pendingOrders,
                'processing' => $processingOrders,
                'shipped' => $shippedOrders,
                'delivered' => $deliveredOrders,
                'cancelled' => $cancelledOrders
            ],
            'revenue' => [
                'total' => $totalRevenue,
                'refunds' => $totalRefunds,
                'byMonth' => $revenueByMonth
            ],
            'topProducts' => $topProducts,
            'topSellers' => $topSellers,
            'returns' => [
                'total' => $totalReturns,
                'pending' => $pendingReturns,
                'approved' => $approvedReturns,
                'rejected' => $rejectedReturns,
                'completed' => $completedReturns
            ]
        ]);
    }
    
    /**
     * Obtenir les statistiques du dashboard pour un client
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function customerStats(Request $request)
    {
        $user = $request->user();
        
        // Statistiques des commandes
        $totalOrders = Order::where('user_id', $user->id)->count();
        $pendingOrders = Order::where('user_id', $user->id)->where('status', 'pending')->count();
        $processingOrders = Order::where('user_id', $user->id)->where('status', 'processing')->count();
        $shippedOrders = Order::where('user_id', $user->id)->where('status', 'shipped')->count();
        $deliveredOrders = Order::where('user_id', $user->id)->where('status', 'delivered')->count();
        
        // Montant total dépensé
        $totalSpent = Order::where('user_id', $user->id)
            ->whereHas('payment', function($query) {
                $query->where('status', 'completed');
            })
            ->sum('total_amount');
            
        // Commandes récentes
        $recentOrders = Order::where('user_id', $user->id)
            ->with(['orderItems.product', 'payment'])
            ->latest()
            ->take(5)
            ->get();
            
        // Statistiques des retours
        $totalReturns = ReturnRequest::where('user_id', $user->id)->count();
        $pendingReturns = ReturnRequest::where('user_id', $user->id)->where('status', 'pending')->count();
        
        return response()->json([
            'orders' => [
                'total' => $totalOrders,
                'pending' => $pendingOrders,
                'processing' => $processingOrders,
                'shipped' => $shippedOrders,
                'delivered' => $deliveredOrders
            ],
            'totalSpent' => $totalSpent,
            'recentOrders' => $recentOrders,
            'returns' => [
                'total' => $totalReturns,
                'pending' => $pendingReturns
            ]
        ]);
    }
    
    /**
     * Obtenir les statistiques de vente pour une période spécifique (admin/vendeur)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function salesStats(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'period' => 'required|in:day,week,month'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = $request->user();
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $period = $request->period;
        
        // Construire la requête de base en fonction du rôle
        if ($user->role === 'admin') {
            $query = Payment::where('amount', '>', 0)
                ->where('status', 'completed');
        } else {
            // Pour un vendeur, filtrer uniquement ses produits
            $productIds = Product::where('user_id', $user->id)->pluck('id');
            
            $orderIds = DB::table('order_items')
                ->whereIn('product_id', $productIds)
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->select('orders.id')
                ->distinct()
                ->pluck('id');
                
            $query = Payment::whereIn('order_id', $orderIds)
                ->where('amount', '>', 0)
                ->where('status', 'completed');
        }
        
        // Filtrer par date
        $query->whereBetween('created_at', [$startDate, $endDate]);
        
        // Grouper par période
        switch ($period) {
            case 'day':
                $query->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(amount) as total')
                )
                ->groupBy('date');
                break;
            case 'week':
                $query->select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('WEEK(created_at) as week'),
                    DB::raw('SUM(amount) as total')
                )
                ->groupBy('year', 'week');
                break;
            case 'month':
                $query->select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('SUM(amount) as total')
                )
                ->groupBy('year', 'month');
                break;
        }
        
        $salesData = $query->orderBy('created_at')->get();
        
        // Calculer le total
        $totalSales = $salesData->sum('total');
        
        return response()->json([
            'period' => $period,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'total_sales' => $totalSales,
            'sales_data' => $salesData
        ]);
    }
};