<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use PDF; // Nécessite l'installation de barryvdh/laravel-dompdf

class InvoiceController extends Controller
{
    /**
     * Générer une facture PDF pour une commande
     *
     * @param int $orderId
     * @return mixed
     */
    public function generate($orderId)
    {
        $user = request()->user();
        $order = Order::with(['user', 'items.product', 'payment'])->findOrFail($orderId);
        
        // Vérifier les autorisations
        if (!$user->isAdmin() && $user->id !== $order->user_id) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à voir cette facture',
            ], 403);
        }
        
        // Vérifier si la commande est payée
        if (!$order->payment || $order->payment->status !== 'completed') {
            return response()->json([
                'message' => 'Cette commande n\'a pas encore été payée',
            ], 400);
        }
        
        // Préparation des données pour la facture
        $data = [
            'order' => $order,
            'company' => [
                'name' => 'Votre Entreprise',
                'address' => '123 Rue du Commerce',
                'city' => '75000 Paris',
                'country' => 'France',
                'phone' => '01 23 45 67 89',
                'email' => 'contact@votre-entreprise.com',
                'siret' => '12345678900001',
                'tva' => 'FR12345678900',
            ],
        ];
        
        // Prévisualisation JSON pour API
        return response()->json([
            'message' => 'Données de facture générées avec succès',
            'invoice_data' => $data,
        ]);
    }
    
    /**
     * Télécharger une facture PDF pour une commande
     *
     * @param int $orderId
     * @return mixed
     */
    public function download($orderId)
    {
        $user = request()->user();
        $order = Order::with(['user', 'items.product', 'payment'])->findOrFail($orderId);
        
        // Vérifier les autorisations
        if (!$user->isAdmin() && $user->id !== $order->user_id) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à télécharger cette facture',
            ], 403);
        }
        
        // Vérifier si la commande est payée
        if (!$order->payment || $order->payment->status !== 'completed') {
            return response()->json([
                'message' => 'Cette commande n\'a pas encore été payée',
            ], 400);
        }
        
        // Préparation des données pour la facture
        $data = [
            'order' => $order,
            'company' => [
                'name' => 'Votre Entreprise',
                'address' => '123 Rue du Commerce',
                'city' => '75000 Paris',
                'country' => 'France',
                'phone' => '01 23 45 67 89',
                'email' => 'contact@votre-entreprise.com',
                'siret' => '12345678900001',
                'tva' => 'FR12345678900',
            ],
        ];
        
        // Pour implémenter réellement:
        // 1. Installer barryvdh/laravel-dompdf: composer require barryvdh/laravel-dompdf
        // 2. Créer une vue pour la facture dans resources/views/invoices/invoice.blade.php
        // 3. Générer le PDF avec:
        // $pdf = PDF::loadView('invoices.invoice', $data);
        // return $pdf->download('facture-' . $order->order_number . '.pdf');
        
        // Retour temporaire pour API
        return response()->json([
            'message' => 'Pour télécharger réellement la facture, installez barryvdh/laravel-dompdf et configurez la vue',
            'invoice_data' => $data,
        ]);
    }
};