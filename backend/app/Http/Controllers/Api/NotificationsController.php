<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Notifications\DatabaseNotification;

class NotificationsController extends Controller
{
    public function index(): JsonResponse
    {
        $notifications = auth()->user()->notifications()->latest()->limit(50)->get();

        return ApiResponse::success([
            'data' => $notifications->map(fn ($n) => [
                'id' => $n->getKey(),
                'title' => $n->data['title'] ?? '',
                'data' => $n->data['data'] ?? [],
                'read_at' => $n->read_at?->toDateTimeString(),
                'created_at' => $n->created_at?->toDateTimeString(),
            ])->values(),
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        $user = auth()->user();

        $count = CacheService::remember('notifications', "unread.{$user->id}", function () use ($user) {
            return $user->unreadNotifications()->count();
        }, 60);

        return ApiResponse::success(['count' => $count]);
    }

    public function read(DatabaseNotification $notification): JsonResponse
    {
        abort_unless((int) $notification->notifiable_id === auth()->id(), 403);

        $notification->markAsRead();

        CacheService::forget('notifications', 'unread.' . auth()->id());

        return ApiResponse::success(null, 'Notifikasi ditandai dibaca.');
    }

    public function readAll(): JsonResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        CacheService::forget('notifications', 'unread.' . auth()->id());

        return ApiResponse::success(null, 'Semua notifikasi ditandai dibaca.');
    }
}