<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ReturnController;
use App\Http\Controllers\Api\StatController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\ClientController;
// Mise à jour de l'import pour utiliser le bon namespace
use App\Http\Controllers\Admin\DashboardController;

// Routes publiques
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Produits & catégories (public)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products/{id}/reviews', [ReviewController::class, 'productReviews']);

// Notifications (fonctionnalité en développement)
Route::get('/notifications', function () {
    return response()->json([
        'data' => [],
        'message' => 'Fonctionnalité en cours de développement'
    ]);
});

// Routes protégées par middleware auth:sanctum
Route::middleware('auth:sanctum')->group(function () {

    // Utilisateur connecté
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Routes client
    Route::middleware('client')->prefix('client')->group(function () {
        Route::get('/dashboard', [ClientController::class, 'dashboard']);
        Route::get('/orders', [ClientController::class, 'orders']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/{id}', [ClientController::class, 'orderDetail']);

        Route::get('/cart', [CartController::class, 'index']);
        Route::post('/cart/add', [CartController::class, 'add']);
        Route::put('/cart/update/{id}', [CartController::class, 'update']);
        Route::delete('/cart/remove/{id}', [CartController::class, 'remove']);

        Route::get('/wishlist', [ClientController::class, 'wishlist']);
        Route::post('/wishlist/add/{productId}', [ClientController::class, 'addToWishlist']);
        Route::delete('/wishlist/remove/{productId}', [ClientController::class, 'removeFromWishlist']);

        Route::post('/products/{id}/review', [ReviewController::class, 'store']);

        Route::post('/orders/{orderId}/return', [ReturnController::class, 'store']);
        Route::get('/returns', [ReturnController::class, 'index']);
        Route::get('/returns/{id}', [ReturnController::class, 'show']);
    });

    // Rétrocompatibilité : commandes
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    // Paiement
    Route::post('/orders/{orderId}/payment', [PaymentController::class, 'processPayment']);
    Route::get('/orders/{orderId}/payment/status', [PaymentController::class, 'getPaymentDetails']);

    // Facture
    Route::get('/orders/{orderId}/invoice', [InvoiceController::class, 'generate']);
    Route::get('/orders/{orderId}/invoice/download', [InvoiceController::class, 'download']);

    // Retours
    Route::post('/orders/{orderId}/return', [ReturnController::class, 'store']);
    Route::get('/returns', [ReturnController::class, 'index']);
    Route::get('/returns/{id}', [ReturnController::class, 'show']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // Panier
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::put('/cart/update/{id}', [CartController::class, 'update']);
    Route::delete('/cart/remove/{id}', [CartController::class, 'remove']);

    // Avis
    Route::post('/products/{id}/review', [ReviewController::class, 'store']);

    // Vendeur
    Route::middleware('vendeur')->prefix('vendor')->group(function () {
        Route::get('/dashboard', [VendorController::class, 'dashboard']);
        Route::get('/stats', [VendorController::class, 'stats']);

        Route::get('/products', [VendorController::class, 'products']);
        Route::post('/products', [VendorController::class, 'storeProduct']);
        Route::get('/products/{id}', [VendorController::class, 'showProduct']);
        Route::put('/products/{id}', [VendorController::class, 'updateProduct']);
        Route::delete('/products/{id}', [VendorController::class, 'deleteProduct']);

        Route::get('/orders', [VendorController::class, 'orders']);
        Route::get('/orders/{id}', [VendorController::class, 'orderDetail']);
        Route::put('/orders/{id}/status', [VendorController::class, 'updateOrderStatus']);

        Route::get('/reviews', [VendorController::class, 'reviews']);
        Route::post('/reviews/{id}/reply', [VendorController::class, 'replyToReview']);

        Route::get('/profile', [VendorController::class, 'vendorProfile']);
        Route::put('/profile', [VendorController::class, 'updateVendorProfile']);

        Route::get('/store/settings', [VendorController::class, 'storeSettings']);
        Route::put('/store/settings', [VendorController::class, 'updateStoreSettings']);
    });

    // Admin
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        // Mise à jour pour utiliser le contrôleur dans le namespace Admin
        Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/users/{id}', [AdminController::class, 'showUser']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);

        // Ajout de routes temporaires pour les fonctionnalités en développement
        Route::get('/orders/recent', function () {
            return response()->json([
                'data' => [],
                'message' => 'Fonctionnalité en cours de développement'
            ]);
        });
        
        Route::get('/products/top', function () {
            return response()->json([
                'data' => [],
                'message' => 'Fonctionnalité en cours de développement'
            ]);
        });
        
        Route::get('/users/top-sellers', function () {
            return response()->json([
                'data' => [],
                'message' => 'Fonctionnalité en cours de développement'
            ]);
        });
        
        Route::get('/stats/sales', function () {
            return response()->json([
                'data' => [],
                'message' => 'Fonctionnalité en cours de développement'
            ]);
        });

        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

        Route::get('/products', [ProductController::class, 'adminIndex']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);

        Route::get('/orders', [OrderController::class, 'adminIndex']);
        Route::get('/orders/{id}', [OrderController::class, 'adminShow']);
        Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);

        Route::get('/vendors', [AdminController::class, 'vendors']);
        Route::get('/vendors/{id}', [AdminController::class, 'showVendor']);
        Route::put('/vendors/{id}/status', [AdminController::class, 'updateVendorStatus']);

        Route::get('/reviews', [ReviewController::class, 'adminIndex']);
        Route::put('/reviews/{id}/status', [ReviewController::class, 'updateStatus']);
        Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
    });
});

// Middleware role (optionnel)
Route::middleware('role:admin')->prefix('role-admin')->group(function () {
    Route::get('/test', [AdminController::class, 'test']);
});
Route::middleware('role:vendeur')->prefix('role-vendeur')->group(function () {
    Route::get('/test', [VendorController::class, 'test']);
});
Route::middleware('role:client')->prefix('role-client')->group(function () {
    Route::get('/test', [ClientController::class, 'test']);
});

// Changement de langue
Route::get('/language/{locale}', function ($locale) {
    if (in_array($locale, ['fr', 'en'])) {
        session()->put('locale', $locale);
    }
    return response()->json(['locale' => session('locale')]);
});