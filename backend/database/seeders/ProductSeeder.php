<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Désactiver la vérification des clés étrangères
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Product::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Créer un utilisateur de test si nécessaire
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'Admin Test',
                'email' => 'admin@naturna.ma',
                'password' => bcrypt('password'),
            ]);
        }

        // Récupérer les catégories
        $categories = Category::all();
        
        // Vérifier si le dossier de stockage public existe, sinon le créer
        if (!Storage::exists('public/products')) {
            Storage::makeDirectory('public/products');
            $this->command->info('Dossier de stockage public/products créé');
        }
        
        $products = [
            [
                'name' => 'Tapis Berbère Traditionnel',
                'description' => 'Magnifique tapis berbère fait à la main, avec des motifs géométriques traditionnels',
                'price' => 1500.00,
                'stock' => 10,
                'stock_threshold' => 5,
                'category_id' => $categories->where('name', 'Tapis')->first()?->id ?? 1,
                'image_source' => 'tapis.jpg',
            ],
            [
                'name' => 'Poterie de Safi',
                'description' => 'Poterie artisanale de Safi, peinte à la main avec des motifs traditionnels',
                'price' => 350.00,
                'stock' => 25,
                'stock_threshold' => 5,
                'category_id' => $categories->where('name', 'Poterie')->first()?->id ?? 2,
                'image_source' => 'poterie.jpg',
            ],
            [
                'name' => 'Bijou en Argent de Tiznit',
                'description' => 'Bijou traditionnel en argent ciselé, fabriqué à Tiznit',
                'price' => 850.00,
                'stock' => 15,
                'stock_threshold' => 3,
                'category_id' => $categories->where('name', 'Bijoux')->first()?->id ?? 3,
                'image_source' => 'bijou.jpg',
            ],
            [
                'name' => 'Lanterne Marocaine',
                'description' => 'Lanterne en métal ciselé avec verre coloré, style traditionnel',
                'price' => 450.00,
                'stock' => 20,
                'stock_threshold' => 5,
                'category_id' => $categories->where('name', 'Éclairage')->first()?->id ?? 4,
                'image_source' => 'lanterne.jpg',
            ],
            [
                'name' => 'Pouffe en Cuir',
                'description' => 'Pouffe traditionnel en cuir tanné, brodé à la main',
                'price' => 650.00,
                'stock' => 12,
                'stock_threshold' => 3,
                'category_id' => $categories->where('name', 'Maroquinerie')->first()?->id ?? 5,
                'image_source' => 'pouffe.jpg',
            ],
        ];

        foreach ($products as $productData) {
            // Extraire l'image source avant de créer le produit
            $imageSource = $productData['image_source'] ?? null;
            unset($productData['image_source']); // Retirer du tableau pour éviter l'erreur
            
            // Préparer les données du produit
            $productData['user_id'] = $user->id;
            $productData['slug'] = Str::slug($productData['name']);
            $productData['active'] = 1;
            
            // Traiter l'image locale si elle existe
            if ($imageSource) {
                $sourcePath = database_path('seeders/images/' . $imageSource);
                
                // Vérifier si le fichier existe
                if (file_exists($sourcePath)) {
                    // Créer un nom de fichier unique dans le storage
                    $destFilename = 'products/' . uniqid() . '_' . $imageSource;
                    
                    // Lire le contenu du fichier et le stocker dans storage/app/public
                    $fileContent = file_get_contents($sourcePath);
                    Storage::put('public/' . $destFilename, $fileContent);
                    
                    // Stocker le chemin relatif dans la base de données
                    $productData['image'] = $destFilename;
                    
                    $this->command->info("Image {$imageSource} copiée avec succès");
                } else {
                    $this->command->error("Image {$imageSource} introuvable dans database/seeders/images");
                    
                    // Utiliser une image par défaut
                    $productData['image'] = 'https://via.placeholder.com/600x400?text=Image+non+trouvée';
                }
            } else {
                // Pas d'image source, utiliser un placeholder
                $productData['image'] = 'https://via.placeholder.com/600x400?text=' . urlencode($productData['name']);
            }
            
            // Créer le produit
            Product::create($productData);
        }
        
        $this->command->info('La table des produits a été vidée et remplie avec de nouvelles données.');
    }
}