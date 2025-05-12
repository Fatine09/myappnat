<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\ReturnItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        if ($user->isAdmin()) {
            $returns = ReturnRequest::with(['order', 'items.orderItem.product', 'user'])->latest()->get();
        } elseif ($user->isVendeur()) {
            // Vendeurs voient les retours pour leurs produits
            $returns = ReturnRequest::whereHas('items.orderItem.product', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['order', 'items.orderItem.product', 'user'])
            ->latest()
            ->get();
        } else {
            // Clients ne voient que leurs propres retours
            $returns = ReturnRequest::where('user_id', $user->id)
                ->with(['order', 'items.orderItem.product'])
                ->latest()
                ->get();
        }
        
        return response()->json($returns);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'reason' => 'required|string',
            'items' => 'required|array',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = $request->user();
        $order = Order::findOrFail($request->order_id);
        
        // Vérifier que l'utilisateur est le propriétaire de la commande
        if ($user->id !== $order->user_id) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à demander un retour pour cette commande',
            ], 403);
        }
        
        // Vérifier si la commande est éligible au retour (par exemple, pas de retour pour les commandes annulées)
        if (in_array($order->status, ['cancelled', 'declined'])) {
            return response()->json([
                'message' => 'Cette commande n\'est pas éligible au retour',
            ], 400);
        }

        // Vérifier si un retour existe déjà pour cette commande
        if (ReturnRequest::where('order_id', $order->id)->exists()) {
            return response()->json([
                'message' => 'Un retour existe déjà pour cette commande',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Créer la demande de retour
            $return = ReturnRequest::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'reason' => $request->reason,
                'status' => 'pending',
            ]);

            // Vérifier et ajouter les éléments de retour
            foreach ($request->items as $item) {
                $orderItem = OrderItem::findOrFail($item['order_item_id']);
                
                // Vérifier que l'élément appartient à la commande
                if ($orderItem->order_id !== $order->id) {
                    throw new \Exception('L\'élément de commande ne correspond pas à la commande');
                }
                
                // Vérifier que la quantité demandée est valide
                if ($item['quantity'] > $orderItem->quantity) {
                    throw new \Exception('La quantité de retour ne peut pas dépasser la quantité commandée');
                }
                
                // Créer l'élément de retour
                ReturnItem::create([
                    'return_id' => $return->id,
                    'order_item_id' => $orderItem->id,
                    'quantity' => $item['quantity'],
                    'reason' => $item['reason'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Demande de retour créée avec succès',
                'return' => ReturnRequest::with(['items.orderItem.product'])->find($return->id),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Une erreur est survenue lors de la création de la demande de retour',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $user = request()->user();
        $return = ReturnRequest::with(['order', 'items.orderItem.product', 'user'])->findOrFail($id);
        
        // Vérifier les autorisations
        if (!$user->isAdmin() && $user->id !== $return->user_id && !$this->userHasProductInReturn($user, $return)) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à voir cette demande de retour',
            ], 403);
        }
        
        return response()->json($return);
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,approved,declined,refunded',
            'admin_notes' => 'nullable|string',
            'refund_amount' => 'required_if:status,refunded|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = $request->user();
        
        // Seuls les administrateurs peuvent modifier le statut de la demande de retour
        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à modifier le statut de cette demande de retour',
            ], 403);
        }
        
        $return = ReturnRequest::findOrFail($id);
        
        try {
            DB::beginTransaction();

            $return->update([
                'status' => $request->status,
                'admin_notes' => $request->admin_notes,
            ]);

            // Si le statut est "approved", mettre à jour les stocks des produits retournés
            if ($request->status === 'approved') {
                foreach ($return->items as $returnItem) {
                    $product = $returnItem->orderItem->product;
                    $product->update([
                        'stock' => $product->stock + $returnItem->quantity,
                    ]);
                }
            }

            // Si le statut est "refunded", enregistrer les détails du remboursement
            if ($request->status === 'refunded') {
                $return->update([
                    'is_refunded' => true,
                    'refund_amount' => $request->refund_amount,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Statut de la demande de retour mis à jour avec succès',
                'return' => $return,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Une erreur est survenue lors de la mise à jour du statut',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    protected function userHasProductInReturn($user, $return)
    {
        // Vérifier si l'utilisateur est un vendeur avec des produits dans ce retour
        foreach ($return->items as $item) {
            if ($item->orderItem->product->user_id === $user->id) {
                return true;
            }
        }
        
        return false;
    }
};