<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'stock_threshold',
        'active',
        'image',
    ];

    // Méthode pour obtenir l'URL complète de l'image
    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return asset('images/default-product.jpg');
        }
        
        // Si c'est déjà une URL complète
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }
        
        // Si c'est un chemin stocké dans la base de données
        return asset('storage/' . $this->image);
    }

    // Vos relations et autres méthodes existantes...
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItem()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Relation avec les nouvelles tables
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
    
    public function stockHistory()
    {
        return $this->hasMany(StockHistory::class);
    }
    
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function hasLowStock()
    {
        return $this->stock <= $this->stock_threshold;
    }
    
    // Méthode utilitaire pour obtenir l'image principale
    public function getPrimaryImage()
    {
        return $this->images()->where('is_primary', true)->first() ?? $this->images()->first();
    }
    
    // Méthode pour ajuster le stock
    public function adjustStock($quantity, $type, $userId, $referenceId = null, $notes = null)
    {
        $previousStock = $this->stock;
        $this->stock += $quantity; // Peut être positif ou négatif
        $this->save();
        
        // Enregistrer l'historique du stock
        StockHistory::create([
            'product_id' => $this->id,
            'user_id' => $userId,
            'previous_stock' => $previousStock,
            'new_stock' => $this->stock,
            'adjustment' => $quantity,
            'type' => $type,
            'reference_id' => $referenceId,
            'notes' => $notes
        ]);
        
        return $this;
    }
}