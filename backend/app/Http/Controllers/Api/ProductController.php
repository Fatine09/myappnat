<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'user'])->where('active', true)->get();
        
        // Ajouter l'URL de l'image à chaque produit
        $products->transform(function ($product) {
            $product->image_url = $product->getImageUrlAttribute();
            return $product;
        });
        
        return response()->json($products);
    }

    public function vendeurProducts(Request $request)
    {
        $products = Product::with('category')
            ->where('user_id', $request->user()->id)
            ->get();
        
        // Ajouter l'URL de l'image à chaque produit
        $products->transform(function ($product) {
            $product->image_url = $product->getImageUrlAttribute();
            return $product;
        });
            
        return response()->json($products);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'stock_threshold' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'user_id' => $request->user()->id,
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . uniqid(),
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
            'stock_threshold' => $request->stock_threshold,
            'active' => true,
            'image' => $imagePath,
        ]);

        // Ajouter l'URL de l'image
        $product->image_url = $product->getImageUrlAttribute();

        return response()->json([
            'message' => 'Produit créé avec succès',
            'product' => $product,
        ], 201);
    }

    public function show($id)
    {
        $product = Product::with(['category', 'user'])->findOrFail($id);
        
        // Ajouter l'URL de l'image
        $product->image_url = $product->getImageUrlAttribute();
        
        return response()->json($product);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // Vérifier si l'utilisateur est autorisé à modifier ce produit
        if ($request->user()->id !== $product->user_id && !$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à modifier ce produit',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'stock_threshold' => 'required|integer|min:0',
            'active' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $imagePath = $product->image;
        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image si elle existe
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . uniqid(),
            'category_id' => $request->category_id,
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
            'stock_threshold' => $request->stock_threshold,
            'active' => $request->active ?? $product->active,
            'image' => $imagePath,
        ]);

        // Ajouter l'URL de l'image
        $product->image_url = $product->getImageUrlAttribute();

        return response()->json([
            'message' => 'Produit mis à jour avec succès',
            'product' => $product,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // Vérifier si l'utilisateur est autorisé à supprimer ce produit
        if ($request->user()->id !== $product->user_id && !$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à supprimer ce produit',
            ], 403);
        }

        // Supprimer l'image si elle existe
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json([
            'message' => 'Produit supprimé avec succès',
        ]);
    }

    public function updateStock(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // Vérifier si l'utilisateur est autorisé à modifier ce produit
        if ($request->user()->id !== $product->user_id && !$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à modifier ce produit',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'stock' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $product->update([
            'stock' => $request->stock,
        ]);

        // Ajouter l'URL de l'image
        $product->image_url = $product->getImageUrlAttribute();

        return response()->json([
            'message' => 'Stock mis à jour avec succès',
            'product' => $product,
        ]);
    }

    public function lowStock(Request $request)
    {
        // Cette méthode ne doit être accessible qu'aux vendeurs et administrateurs
        $userId = $request->user()->id;
        $isAdmin = $request->user()->isAdmin();

        $query = Product::query();
        
        // Si l'utilisateur n'est pas un administrateur, filtrer par utilisateur
        if (!$isAdmin) {
            $query->where('user_id', $userId);
        }
        
        $lowStockProducts = $query->whereRaw('stock <= stock_threshold')
            ->with('category')
            ->get();

        // Ajouter l'URL de l'image à chaque produit
        $lowStockProducts->transform(function ($product) {
            $product->image_url = $product->getImageUrlAttribute();
            return $product;
        });

        return response()->json($lowStockProducts);
    }
}