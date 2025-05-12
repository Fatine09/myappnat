<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Récupère les avis d'un produit spécifique
     */
    public function productReviews($id)
    {
        // Pour l'instant, retournez simplement un tableau vide
        return response()->json([
            'message' => 'Cette fonctionnalité est en cours de développement',
            'product_id' => $id,
            'reviews' => []
        ]);
    }

    /**
     * Enregistre un nouvel avis
     */
    public function store(Request $request, $id)
    {
        // Pour l'instant, retournez simplement un message de succès
        return response()->json([
            'message' => 'Avis enregistré avec succès (simulé)',
            'product_id' => $id
        ], 201);
    }

    /**
     * Liste tous les avis (pour l'admin)
     */
    public function adminIndex()
    {
        return response()->json([
            'message' => 'Cette fonctionnalité est en cours de développement',
            'reviews' => []
        ]);
    }

    /**
     * Met à jour le statut d'un avis
     */
    public function updateStatus(Request $request, $id)
    {
        return response()->json([
            'message' => 'Statut de l\'avis mis à jour avec succès (simulé)',
            'review_id' => $id
        ]);
    }

    /**
     * Supprime un avis
     */
    public function destroy($id)
    {
        return response()->json([
            'message' => 'Avis supprimé avec succès (simulé)',
            'review_id' => $id
        ]);
    }
}