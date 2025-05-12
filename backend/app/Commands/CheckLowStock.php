<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class CheckLowStock extends Command
{
    protected $signature = 'stock:check-low';
    protected $description = 'Check for low stock products and notify the sellers';

    public function handle()
    {
        $lowStockProducts = Product::whereRaw('stock <= stock_threshold')->get();

        foreach ($lowStockProducts as $product) {
            // Logique pour notifier le vendeur
            // Par exemple, envoyer une notification ou un email
            $this->info("Produit {$product->name} a un stock bas de {$product->stock} unités.");
        }

        return 0;
    }
};