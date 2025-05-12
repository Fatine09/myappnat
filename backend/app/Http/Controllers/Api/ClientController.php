<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\Wishlist;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    /**
     * Affiche le tableau de bord du client
     */
    public function dashboard()
    {
        $user = auth()->user();
        
        // Récupérer les commandes récentes
        $recentOrders = Order::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // Récupérer le nombre total de commandes
        $ordersCount = Order::where('user_id', $user->id)->count();
        
        // Récupérer le nombre d'articles dans la liste de souhaits
        $wishlistCount = Wishlist::where('user_id', $user->id)->count();
        
        // Récupérer le nombre d'avis postés par l'utilisateur
        $reviewsCount = $user->reviews()->count();
        
        // Récupérer le montant total dépensé
        $totalSpent = Order::where('user_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->sum('total');
        
        return response()->json([
            'recent_orders' => $recentOrders,
            'stats' => [
                'orders_count' => $ordersCount,
                'wishlist_count' => $wishlistCount,
                'reviews_count' => $reviewsCount,
                'total_spent' => $totalSpent
            ]
        ]);
    }
    
    /**
     * Liste toutes les commandes du client
     */
    public function orders(Request $request)
    {
        $user = auth()->user();
        
        $query = Order::where('user_id', $user->id);
        
        // Filtrage par statut
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Tri
        $sortField = $request->input('sort', 'created_at');
        $sortOrder = $request->input('order', 'desc');
        $query->orderBy($sortField, $sortOrder);
        
        // Pagination
        $perPage = $request->input('per_page', 10);
        $orders = $query->paginate($perPage);
        
        // Formater les données pour l'affichage
        $formattedOrders = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->id, // Ou un format personnalisé
                'created_at' => $order->created_at,
                'status' => $order->status,
                'status_label' => $this->getOrderStatusLabel($order->status),
                'total' => $order->total,
                'items_count' => $order->items->count()
            ];
        });
        
        return response()->json([
            'orders' => $formattedOrders,
            'pagination' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage()
            ]
        ]);
    }
    
    /**
     * Affiche les détails d'une commande
     */
    public function orderDetail($id)
    {
        $user = auth()->user();
        $order = Order::with('items.product')->where('user_id', $user->id)->findOrFail($id);
        
        // Formater les données pour l'affichage
        $formattedOrder = [
            'id' => $order->id,
            'order_number' => $order->id, // Ou un format personnalisé
            'created_at' => $order->created_at,
            'status' => $order->status,
            'status_label' => $this->getOrderStatusLabel($order->status),
            'subtotal' => $order->subtotal,
            'shipping_cost' => $order->shipping_cost,
            'tax' => $order->tax,
            'discount' => $order->discount,
            'total' => $order->total,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'payment_status_label' => $this->getPaymentStatusLabel($order->payment_status),
            'tracking_number' => $order->tracking_number,
            'tracking_url' => $order->tracking_url,
            'shipping_address' => [
                'first_name' => $order->shipping_address_first_name,
                'last_name' => $order->shipping_address_last_name,
                'address' => $order->shipping_address,
                'city' => $order->shipping_address_city,
                'postal_code' => $order->shipping_address_postal_code,
                'country' => $order->shipping_address_country,
                'phone' => $order->shipping_address_phone
            ],
            'items' => $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->name,
                    'product_slug' => $item->product ? $item->product->slug : null,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->price,
                    'total_price' => $item->price * $item->quantity,
                    'image_url' => $item->product ? $item->product->image_url : null,
                    'variant' => $item->options,
                    'artisan_name' => $item->product ? ($item->product->user ? $item->product->user->name : null) : null
                ];
            }),
            'is_reviewed' => $order->is_reviewed
        ];
        
        return response()->json([
            'order' => $formattedOrder
        ]);
    }
    
    /**
     * Affiche la liste de souhaits du client
     */
    public function wishlist(Request $request)
    {
        $user = auth()->user();
        
        $perPage = $request->input('per_page', 12);
        $wishlistItems = Wishlist::with('product')
            ->where('user_id', $user->id)
            ->paginate($perPage);
        
        // Formater les données pour l'affichage
        $formattedItems = $wishlistItems->map(function ($item) {
            if (!$item->product) {
                return null;
            }
            
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product' => [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'slug' => $item->product->slug,
                    'price' => $item->product->price,
                    'image_url' => $item->product->image_url,
                    'in_stock' => $item->product->stock_quantity > 0,
                    'stock_quantity' => $item->product->stock_quantity,
                    'vendor_name' => $item->product->user ? $item->product->user->name : null
                ],
                'added_at' => $item->created_at
            ];
        })->filter();
        
        return response()->json([
            'wishlist' => $formattedItems,
            'pagination' => [
                'total' => $wishlistItems->total(),
                'per_page' => $wishlistItems->perPage(),
                'current_page' => $wishlistItems->currentPage(),
                'last_page' => $wishlistItems->lastPage()
            ]
        ]);
    }
    
    /**
     * Ajoute un produit à la liste de souhaits
     */
    public function addToWishlist($productId)
    {
        $user = auth()->user();
        
        // Vérifier si le produit existe
        $product = Product::findOrFail($productId);
        
        // Vérifier si le produit est déjà dans la liste de souhaits
        $existingItem = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();
        
        if ($existingItem) {
            return response()->json([
                'message' => 'Ce produit est déjà dans votre liste de souhaits'
            ], 422);
        }
        
        // Ajouter le produit à la liste de souhaits
        $wishlistItem = new Wishlist();
        $wishlistItem->user_id = $user->id;
        $wishlistItem->product_id = $productId;
        $wishlistItem->save();
        
        return response()->json([
            'message' => 'Produit ajouté à la liste de souhaits',
            'wishlist_item' => $wishlistItem
        ], 201);
    }
    
    /**
     * Supprime un produit de la liste de souhaits
     */
    public function removeFromWishlist($productId)
    {
        $user = auth()->user();
        
        // Trouver l'élément de la liste de souhaits
        $wishlistItem = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();
        
        if (!$wishlistItem) {
            return response()->json([
                'message' => 'Ce produit n\'est pas dans votre liste de souhaits'
            ], 404);
        }
        
        // Supprimer l'élément
        $wishlistItem->delete();
        
        return response()->json([
            'message' => 'Produit retiré de la liste de souhaits'
        ]);
    }
    
    /**
     * Obtenir le libellé du statut de la commande
     */
    private function getOrderStatusLabel($status)
    {
        $labels = [
            'pending' => 'En attente',
            'processing' => 'En traitement',
            'shipped' => 'Expédiée',
            'delivered' => 'Livrée',
            'cancelled' => 'Annulée',
            'returned' => 'Retournée',
        ];
        
        return $labels[$status] ?? $status;
    }
    
    /**
     * Obtenir le libellé du statut de paiement
     */
    private function getPaymentStatusLabel($status)
    {
        $labels = [
            'pending' => 'En attente',
            'paid' => 'Payé',
            'failed' => 'Échoué',
            'refunded' => 'Remboursé',
        ];
        
        return $labels[$status] ?? $status;
    }
}