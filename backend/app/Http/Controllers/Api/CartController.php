<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * Afficher le panier de l'utilisateur
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $cartItems = Cart::with('product')->where('user_id', $user->id)->get();
        
        // Calculer le total du panier
        $total = 0;
        foreach ($cartItems as $item) {
            if ($item->product) {
                $total += $item->product->price * $item->quantity;
            }
        }
        
        // Formater la réponse
        $formattedItems = $cartItems->map(function ($item) {
            if (!$item->product) {
                return null;
            }
            
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'product' => [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'price' => $item->product->price,
                    'image_url' => $item->product->getImageUrlAttribute(),
                    'stock' => $item->product->stock,
                ],
                'total_price' => $item->product->price * $item->quantity,
                'added_at' => $item->created_at
            ];
        })->filter()->values();
        
        return response()->json([
            'cart_items' => $formattedItems,
            'total' => $total,
            'items_count' => $cartItems->sum('quantity')
        ]);
    }
    
    /**
     * Ajouter un produit au panier
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        $user = $request->user();
        $productId = $request->product_id;
        $quantity = $request->quantity;
        
        // Vérifier si le produit existe et s'il est en stock
        $product = Product::findOrFail($productId);
        
        if ($product->stock < $quantity) {
            return response()->json([
                'message' => 'Stock insuffisant',
                'available_stock' => $product->stock
            ], 422);
        }
        
        // Vérifier si le produit est déjà dans le panier
        $cartItem = Cart::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();
        
        if ($cartItem) {
            // Mettre à jour la quantité si le produit existe déjà
            $newQuantity = $cartItem->quantity + $quantity;
            
            // Vérifier que la nouvelle quantité ne dépasse pas le stock
            if ($product->stock < $newQuantity) {
                return response()->json([
                    'message' => 'Stock insuffisant pour ajouter cette quantité supplémentaire',
                    'available_stock' => $product->stock,
                    'current_quantity' => $cartItem->quantity
                ], 422);
            }
            
            $cartItem->quantity = $newQuantity;
            $cartItem->save();
            
            $message = 'Quantité mise à jour dans le panier';
        } else {
            // Créer un nouvel élément de panier
            $cartItem = new Cart();
            $cartItem->user_id = $user->id;
            $cartItem->product_id = $productId;
            $cartItem->quantity = $quantity;
            $cartItem->save();
            
            $message = 'Produit ajouté au panier';
        }
        
        // Récupérer le panier mis à jour
        $cartItems = Cart::with('product')->where('user_id', $user->id)->get();
        $total = $cartItems->reduce(function ($carry, $item) {
            return $carry + ($item->product->price * $item->quantity);
        }, 0);
        
        return response()->json([
            'message' => $message,
            'cart_item' => [
                'id' => $cartItem->id,
                'product_id' => $cartItem->product_id,
                'quantity' => $cartItem->quantity,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image_url' => $product->getImageUrlAttribute(),
                ],
                'total_price' => $product->price * $cartItem->quantity
            ],
            'cart_total' => $total,
            'items_count' => $cartItems->sum('quantity')
        ], 201);
    }
    
    /**
     * Mettre à jour la quantité d'un produit dans le panier
     *
     * @param Request $request
     * @param int $id ID de l'élément dans le panier
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        $user = $request->user();
        $quantity = $request->quantity;
        
        // Trouver l'élément du panier
        $cartItem = Cart::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        // Vérifier le stock disponible
        $product = Product::findOrFail($cartItem->product_id);
        
        if ($product->stock < $quantity) {
            return response()->json([
                'message' => 'Stock insuffisant',
                'available_stock' => $product->stock
            ], 422);
        }
        
        // Mettre à jour la quantité
        $cartItem->quantity = $quantity;
        $cartItem->save();
        
        // Récupérer le panier mis à jour
        $cartItems = Cart::with('product')->where('user_id', $user->id)->get();
        $total = $cartItems->reduce(function ($carry, $item) {
            return $carry + ($item->product->price * $item->quantity);
        }, 0);
        
        return response()->json([
            'message' => 'Quantité mise à jour',
            'cart_item' => [
                'id' => $cartItem->id,
                'product_id' => $cartItem->product_id,
                'quantity' => $cartItem->quantity,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image_url' => $product->getImageUrlAttribute(),
                ],
                'total_price' => $product->price * $cartItem->quantity
            ],
            'cart_total' => $total,
            'items_count' => $cartItems->sum('quantity')
        ]);
    }
    
    /**
     * Supprimer un produit du panier
     *
     * @param int $id ID de l'élément dans le panier
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove($id)
    {
        $user = request()->user();
        
        // Trouver l'élément du panier
        $cartItem = Cart::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        // Supprimer l'élément
        $cartItem->delete();
        
        // Récupérer le panier mis à jour
        $cartItems = Cart::with('product')->where('user_id', $user->id)->get();
        $total = $cartItems->reduce(function ($carry, $item) {
            return $carry + ($item->product->price * $item->quantity);
        }, 0);
        
        return response()->json([
            'message' => 'Produit retiré du panier',
            'cart_total' => $total,
            'items_count' => $cartItems->sum('quantity')
        ]);
    }
    
    /**
     * Vider le panier
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clear()
    {
        $user = request()->user();
        
        // Supprimer tous les éléments du panier
        Cart::where('user_id', $user->id)->delete();
        
        return response()->json([
            'message' => 'Panier vidé',
            'cart_total' => 0,
            'items_count' => 0
        ]);
    }
    
    /**
     * Obtenir le nombre d'articles dans le panier
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function count()
    {
        $user = request()->user();
        $count = Cart::where('user_id', $user->id)->sum('quantity');
        
        return response()->json([
            'count' => $count
        ]);
    }
}