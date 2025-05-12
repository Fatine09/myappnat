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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function dashboard()
    {
        // Statistiques globales
        $totalUsers = User::count();
        $totalProducts = Product::count();
        $totalOrders = Order::count();
        $totalSales = Payment::where('status', 'completed')->sum('amount');
        $totalReturns = ReturnRequest::count();
        
        // Ventes par jour sur les 7 derniers jours
        $salesByDay = Payment::where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Commandes par statut
        $ordersByStatus = Order::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();
        
        // Produits les plus vendus
        $topProducts = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select('products.id', 'products.name', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();
        
        // Utilisateurs récemment inscrits
        $recentUsers = User::latest()->limit(5)->get();
        
        // Commandes récentes
        $recentOrders = Order::with('user')->latest()->limit(5)->get();
        
        return response()->json([
            'totalUsers' => $totalUsers,
            'totalProducts' => $totalProducts,
            'totalOrders' => $totalOrders,
            'totalSales' => $totalSales,
            'totalReturns' => $totalReturns,
            'salesByDay' => $salesByDay,
            'ordersByStatus' => $ordersByStatus,
            'topProducts' => $topProducts,
            'recentUsers' => $recentUsers,
            'recentOrders' => $recentOrders,
        ]);
    }

    public function users()
    {
        $users = User::all();
        return response()->json($users);
    }

    public function showUser($id)
    {
        $user = User::with(['orders', 'products'])->findOrFail($id);
        return response()->json($user);
    }

    public function createUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:client,vendeur,admin',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => $user,
        ], 201);
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|string|in:client,vendeur,admin',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        return response()->json([
            'message' => 'Utilisateur mis à jour avec succès',
            'user' => $user,
        ]);
    }

    public function resetUserPassword(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::findOrFail($id);
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Mot de passe réinitialisé avec succès',
        ]);
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        
        // Vérifier que l'utilisateur n'est pas l'administrateur actuel
        if ($user->id === request()->user()->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas supprimer votre propre compte',
            ], 400);
        }
        
        $user->delete();

        return response()->json([
            'message' => 'Utilisateur supprimé avec succès',
        ]);
    }

    public function generateInvoicePdf($orderId)
    {
        $order = Order::with(['user', 'items.product', 'payment'])->findOrFail($orderId);
        
        // Dans un cas réel, vous utiliseriez une bibliothèque comme dompdf, barryvdh/laravel-dompdf
        // ou mpdf pour générer le PDF. Pour cet exemple, nous retournons simplement les données.
        
        return response()->json([
            'message' => 'Cette fonctionnalité nécessite l\'installation d\'une bibliothèque PDF',
            'order' => $order,
        ]);
    }
}
