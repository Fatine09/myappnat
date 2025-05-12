<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
    ];

    // Méthode pour obtenir l'URL complète de l'image
    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return asset('images/placeholder-category.jpg');
        }
        
        // Si c'est déjà une URL complète
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }
        
        // Si c'est un chemin stocké dans la base de données
        return asset('storage/' . $this->image);
    }

    // Vos relations existantes...
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}