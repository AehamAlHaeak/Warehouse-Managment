<?php

namespace App\Http\Controllers;


use Exception;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class Notification_controller extends Controller
{
   public function allNotifications()
    {
        try{
      
        $user = auth()->user() ?? auth('employee')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json(["notifications"=>$user->notifications],202);
    }
    catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
}

    
    public function readNotifications(Request $request)
    {
        $user = auth()->user() ?? auth('employee')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json(["notifications"=>$user->readNotifications],202);
    }

  
    public function markAsRead(Request $request, $id)
    {
        $user = auth()->user() ?? auth('employee')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $notification = $user->notifications()->where('id', $id)->first();

        if (!$notification) {
            return response()->json(['error' => 'Notification not found'], 404);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read'],202);
    }

    public function unreadNotifications()
{
   $user = auth()->user() ?? auth('employee')->user();
    $unread = $user->unreadNotifications;

    return response()->json(["uneread notification"=>$unread],202);
}
}
