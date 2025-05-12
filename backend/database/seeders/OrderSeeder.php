<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run()
    {
        // Récupérer des clients aléatoires
        $clients = User::where('role', 'client')->get();
        
        if ($clients->isEmpty()) {
            $this->command->warn('Aucun client trouvé. Assurez-vous que UserSeeder a été exécuté.');
            return;
        }
        
        // Récupérer les produits
        $products = Product::where('active', 1)->get();
        
        if ($products->isEmpty()) {
            $this->command->warn('Aucun produit actif trouvé. Assurez-vous que ProductSeeder a été exécuté.');
            return;
        }
        
        // Statuts possibles pour les commandes marocaines - CORRIGÉ ICI
        $statuses = ['pending', 'processing', 'completed', 'delivered', 'cancelled'];
        
        // Méthodes de paiement populaires au Maroc
        $paymentMethods = ['cash_on_delivery', 'credit_card', 'bank_transfer'];
        
        // Créer des commandes pour chaque client
        foreach ($clients as $client) {
            // Générer 1 à 3 commandes par client
            $orderCount = rand(1, 3);
            
            for ($i = 0; $i < $orderCount; $i++) {
                // Créer une commande
                $status = $statuses[array_rand($statuses)];
                $order = Order::create([
                    'user_id' => $client->id,
                    'order_number' => 'NAT-' . strtoupper(uniqid()),
                    'status' => $status,
                    'total_amount' => 0, // Sera mis à jour après l'ajout des éléments
                    'shipping_address' => json_encode([
                        'street' => 'Avenue Mohammed V',
                        'city' => ['Casablanca', 'Rabat', 'Marrakech', 'Fès'][rand(0, 3)],
                        'postal_code' => rand(10000, 99999),
                        'country' => 'Maroc'
                    ]),
                    'billing_address' => json_encode([
                        'street' => 'Avenue Hassan II',
                        'city' => ['Casablanca', 'Rabat', 'Marrakech', 'Fès'][rand(0, 3)],
                        'postal_code' => rand(10000, 99999),
                        'country' => 'Maroc'
                    ]),
                    'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                    'created_at' => now()->subDays(rand(0, 30)), // Commandes des 30 derniers jours
                ]);
                
                // Ajouter 1 à 4 produits à la commande
                $orderProducts = $products->random(rand(1, 4));
                $totalAmount = 0;
                
                foreach ($orderProducts as $product) {
                    $quantity = rand(1, 2);
                    $price = $product->price;
                    
                    // Vérifier le stock disponible
                    if ($product->stock >= $quantity) {
                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'quantity' => $quantity,
                            'price' => $price,
                            'total_amount' => $quantity * $price, 
                        ]);
                        
                        $totalAmount += $price * $quantity;
                        
                        // Décrémenter le stock si la commande n'est pas annulée
                        if ($status !== 'cancelled') {
                            $product->decrement('stock', $quantity);
                        }
                    }
                }
                
                // Ajouter les frais de livraison (gratuit au-dessus de 500 MAD)
                $shippingCost = $totalAmount >= 500 ? 0 : 50;
                $totalAmount += $shippingCost;
                
                // Mettre à jour le montant total de la commande
                $order->update([
                    'total_amount' => $totalAmount,
                    'shipping_cost' => $shippingCost,
                ]);
                
                // Créer un paiement pour les commandes non annulées et non en attente
                if (!in_array($status, ['cancelled', 'pending'])) {
                    Payment::create([
                        'order_id' => $order->id,
                        'payment_id' => 'PAY-' . strtoupper(uniqid()),
                        'amount' => $totalAmount,
                        'currency' => 'MAD', // Dirham marocain
                        'status' => $status === 'delivered' ? 'completed' : 'pending',
                        'payment_details' => json_encode([
                            'method' => $order->payment_method,
                            'date' => now()->format('Y-m-d H:i:s'),
                            'reference' => 'REF-' . uniqid(),
                        ]),
                    ]);
                }
            }
        }
        
        $this->command->info('OrderSeeder terminé avec succès !');
    }
}