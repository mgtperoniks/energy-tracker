<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $service;

    public function __construct(NotificationService $service)
    {
        $this->service = $service;
    }

    /**
     * Get unread count for the badge
     */
    public function count()
    {
        return response()->json([
            'count' => $this->service->unreadCount()
        ]);
    }

    /**
     * Get latest unread notifications for dropdown
     */
    public function latest()
    {
        $notifications = $this->service->latestNotifications(10);
        
        $html = view('partials.notification_items', compact('notifications'))->render();
        
        return response()->json([
            'html' => $html,
            'count' => $notifications->count()
        ]);
    }

    /**
     * Mark a specific notification as read
     */
    public function read($id)
    {
        $this->service->markAsRead($id);
        return response()->json(['success' => true]);
    }

    /**
     * Mark all as read
     */
    public function readAll()
    {
        $this->service->markAllAsRead();
        return response()->json(['success' => true]);
    }
}
