<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => [],
            'message' => 'Fonctionnalité en cours de développement'
        ]);
    }
    
    public function markAsRead($id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue'
        ]);
    }
    
    public function markAllAsRead()
    {
        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications marquées comme lues'
        ]);
    }
}