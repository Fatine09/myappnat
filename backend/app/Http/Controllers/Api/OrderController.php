<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Notifications\OrderConfirmation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        if ($user->isAdmin()) {
            $orders = Order::with(['user', 'items.product', 'payment'])->latest()->get();
        } elseif ($user->isVendeur()) {
            // Vendeurs voient les commandes contenant leurs produits
            $orders = Order::whereHas('items.product', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['user', 'items.product', 'payment'])
            ->latest()
            ->get();
        } else {
            // Clients ne voient que leurs propres commandes
            $orders = Order::where('user_id', $user->id)
                ->with(['items.product', 'payment'])
                ->latest()
                ->get();
        }
        
        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|string',
            'billing_address' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = $request->user();
        $items = $request->items;
        $totalAmount = 0;

        // Vérifier la disponibilité des stocks et calculer le montant total
        foreach ($items as $item) {
            $product = Product::findOrFail($item['product_id']);
            
            if ($product->stock < $item['quantity']) {
                return response()->json([
                    'message' => "Stock insuffisant pour le produit: {$product->name}",
                ], 400);
            }
            
            $totalAmount += $product->price * $item['quantity'];
        }

        try {
            DB::beginTransaction();

            // Créer la commande
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'shipping_address' => $request->shipping_address,
                'billing_address' => $request->billing_address,
            ]);

            // Créer les éléments de la commande et mettre à jour les stocks
            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ]);
                
                // Mettre à jour le stock
                $product->update([
                    'stock' => $product->stock - $item['quantity'],
                ]);
            }

            DB::commit();

            // Envoyer une notification par email (à implémenter)
            // $user->notify(new OrderConfirmation($order));

            return response()->json([
                'message' => 'Commande créée avec succès',
                'order' => Order::with(['items.product', 'payment'])->find($order->id),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Une erreur est survenue lors de la création de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $user = request()->user();
        $order = Order::with(['items.product', 'payment'])->findOrFail($id);
        
        // Vérifier les autorisations
        if (!$user->isAdmin() && $user->id !== $order->user_id && !$this->userHasProductInOrder($user, $order)) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à voir cette commande',
            ], 403);
        }
        
        return response()->json($order);
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,processing,completed,declined,cancelled,refunded',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = $request->user();
        $order = Order::findOrFail($id);
        
        // Seuls les administrateurs peuvent modifier le statut de la commande
        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à modifier le statut de cette commande',
            ], 403);
        }
        
        $order->update([
            'status' => $request->status,
        ]);
        
        return response()->json([
            'message' => 'Statut de la commande mis à jour avec succès',
            'order' => $order,
        ]);
    }

    protected function userHasProductInOrder($user, $order)
    {
        // Vérifier si l'utilisateur est un vendeur avec des produits dans cette commande
        foreach ($order->items as $item) {
            if ($item->product->user_id === $user->id) {
                return true;
            }
        }
        
        return false;
    }
};