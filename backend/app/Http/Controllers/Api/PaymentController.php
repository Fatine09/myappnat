<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function processPayment(Request $request, $orderId)
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|string|in:credit_card,paypal',
            'payment_details' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = $request->user();
        $order = Order::findOrFail($orderId);
        
        // Vérifier que l'utilisateur est le propriétaire de la commande
        if ($user->id !== $order->user_id) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à effectuer ce paiement',
            ], 403);
        }
        
        // Vérifier si la commande est déjà payée
        if ($order->payment && $order->payment->status === 'completed') {
            return response()->json([
                'message' => 'Cette commande a déjà été payée',
            ], 400);
        }

        try {
            // Cette partie est simplifiée. Dans un cas réel, vous devriez utiliser
            // une passerelle de paiement comme Stripe, PayPal, etc.
            // Ici, nous simulons un paiement réussi
            
            // Créer ou mettre à jour le paiement
            $payment = Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'payment_id' => 'PAY-' . strtoupper(uniqid()),
                    'amount' => $order->total_amount,
                    'currency' => 'EUR',
                    'status' => 'completed',
                    'payment_details' => $request->payment_details,
                ]
            );
            
            // Mettre à jour le statut de la commande
            $order->update([
                'status' => 'processing',
                'payment_method' => $request->payment_method,
            ]);
            
            return response()->json([
                'message' => 'Paiement traité avec succès',
                'payment' => $payment,
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors du traitement du paiement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getPaymentDetails($orderId)
    {
        $user = request()->user();
        $order = Order::with('payment')->findOrFail($orderId);
        
        // Vérifier les autorisations
        if (!$user->isAdmin() && $user->id !== $order->user_id) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à voir ces détails de paiement',
            ], 403);
        }
        
        if (!$order->payment) {
            return response()->json([
                'message' => 'Aucun paiement trouvé pour cette commande',
            ], 404);
        }
        
        return response()->json($order->payment);
    }
};