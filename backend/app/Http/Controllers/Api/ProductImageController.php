<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Product;
use Illuminate\Support\Str;

class ProductImageController extends Controller
{
    /**
     * Upload une nouvelle image pour un produit
     *
     * @param  Request  $request
     * @param  int  $productId
     * @return \Illuminate\Http\Response
     */
    public function upload(Request $request, $productId)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Vérifier si le produit existe
        $product = Product::findOrFail($productId);

        // Supprimer l'ancienne image si elle existe
        if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
            Storage::disk('public')->delete($product->image_path);
        }

        // Générer un nom de fichier unique
        $filename = Str::uuid() . '.' . $request->file('image')->getClientOriginalExtension();
        
        // Stocker l'image dans le dossier images (que vous avez créé manuellement)
        $path = $request->file('image')->storeAs('images', $filename, 'public');

        // Mettre à jour le chemin de l'image dans la base de données
        $product->image_path = $path;
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Image uploadée avec succès',
            'path' => $path,
            'url' => asset('storage/' . $path)
        ]);
    }

    /**
     * Récupère l'image d'un produit
     *
     * @param  int  $productId
     * @return \Illuminate\Http\Response
     */
    public function show($productId)
    {
        $product = Product::findOrFail($productId);

        if (!$product->image_path) {
            return response()->json([
                'success' => false,
                'message' => 'Ce produit n\'a pas d\'image'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'path' => $product->image_path,
            'url' => asset('storage/' . $product->image_path)
        ]);
    }

    /**
     * Supprime l'image d'un produit
     *
     * @param  int  $productId
     * @return \Illuminate\Http\Response
     */
    public function destroy($productId)
    {
        $product = Product::findOrFail($productId);

        if (!$product->image_path) {
            return response()->json([
                'success' => false,
                'message' => 'Ce produit n\'a pas d\'image'
            ], 404);
        }

        // Supprimer le fichier du stockage
        if (Storage::disk('public')->exists($product->image_path)) {
            Storage::disk('public')->delete($product->image_path);
        }

        // Mettre à jour la base de données
        $product->image_path = null;
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Image supprimée avec succès'
        ]);
    }

    /**
     * Upload plusieurs images pour un produit (pour les produits avec galerie)
     * 
     * @param Request $request
     * @param int $productId
     * @return \Illuminate\Http\Response
     */
    public function uploadMultiple(Request $request, $productId)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $product = Product::findOrFail($productId);
        $uploadedPaths = [];

        foreach ($request->file('images') as $image) {
            $filename = Str::uuid() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('images/products/' . $productId, $filename, 'public');
            $uploadedPaths[] = [
                'path' => $path,
                'url' => asset('storage/' . $path)
            ];
            
            // Si vous avez une table pour les images multiples
            // $product->images()->create(['path' => $path]);
        }

        return response()->json([
            'success' => true,
            'message' => count($uploadedPaths) . ' images uploadées avec succès',
            'images' => $uploadedPaths
        ]);
    }
}