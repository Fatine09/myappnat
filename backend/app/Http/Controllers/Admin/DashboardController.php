<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getStats()
    {
        // Nombre total d'utilisateurs
        $usersCount = User::count();

        // Nombre total de produits
        $productsCount = Product::count();

        // Nombre total de commandes
        $ordersCount = Order::count();

        // Exemple de données dynamiques que tu peux également récupérer
        $latestUsers = User::orderBy('created_at', 'desc')->limit(5)->get();
        $latestOrders = Order::orderBy('created_at', 'desc')->limit(5)->get();

        // Retourner les statistiques sous forme de réponse JSON
        return response()->json([
            'users_count' => $usersCount,
            'products_count' => $productsCount,
            'orders_count' => $ordersCount,
            'latest_users' => $latestUsers,
            'latest_orders' => $latestOrders,
        ]);
    }
}
