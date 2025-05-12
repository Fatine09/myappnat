<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CategorySeeder extends Seeder
{
    public function run()
    {
        // S'assurer que le dossier de stockage existe
        if (!Storage::disk('public')->exists('categories')) {
            Storage::disk('public')->makeDirectory('categories');
        }

        $categories = [
            [
                'name' => 'Tapis', 
                'description' => 'Tapis berbères et autres textiles de sol',
                'image' => 'categories/tapis.jpg'
            ],
            [
                'name' => 'Poterie', 
                'description' => 'Poteries et céramiques artisanales',
                'image' => 'categories/poterie.jpg'
            ],
            [
                'name' => 'Bijoux', 
                'description' => 'Bijoux traditionnels en argent et or',
                'image' => 'categories/bijoux.jpg'
            ],
            [
                'name' => 'Éclairage', 
                'description' => 'Lampes et lanternes traditionnelles',
                'image' => 'categories/eclairage.jpg'
            ],
            [
                'name' => 'Maroquinerie', 
                'description' => 'Articles en cuir fait main',
                'image' => 'categories/maroquinerie.jpg'
            ],
            [
                'name' => 'Textiles', 
                'description' => 'Tissus et vêtements traditionnels',
                'image' => 'categories/textiles.jpg'
            ],
            [
                'name' => 'Bois sculpté', 
                'description' => 'Objets décoratifs en bois sculpté',
                'image' => 'categories/bois-sculpte.jpg'
            ],
            [
                'name' => 'Décoration', 
                'description' => 'Objets de décoration artisanale',
                'image' => 'categories/decoration.jpg'
            ],
        ];

        // Copier les images du dossier seeders/images/categories vers le stockage public
        $seedImagesPath = database_path('seeders/images/categories');
        if (File::exists($seedImagesPath)) {
            foreach ($categories as $categoryData) {
                $imageName = basename($categoryData['image']);
                $sourcePath = $seedImagesPath . '/' . $imageName;
                
                if (File::exists($sourcePath)) {
                    $destinationPath = storage_path('app/public/categories/' . $imageName);
                    // Créer le répertoire si nécessaire
                    if (!File::exists(dirname($destinationPath))) {
                        File::makeDirectory(dirname($destinationPath), 0755, true);
                    }
                    // Copier l'image
                    File::copy($sourcePath, $destinationPath);
                }
            }
        }

        foreach ($categories as $categoryData) {
            Category::updateOrCreate(
                ['slug' => Str::slug($categoryData['name'])],
                [
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'],
                    'image' => $categoryData['image'],
                ]
            );
        }
    }
}