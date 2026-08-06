<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Mark a specific notification as read and redirect to the target resource.
     */
    public function read(string $id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $data = $notification->data;
        $transferId = $data['transfer_id'] ?? null;
        $orderId = $data['order_id'] ?? null;

        if ($orderId) {
            return redirect()->route('admin.orders.show', $orderId);
        }

        if (!$transferId) {
            return redirect()->back();
        }

        $user = auth()->user();
        if ($user->isAdmin()) {
            return redirect()->route('admin.stock-transfers.show', $transferId);
        } elseif ($user->isOwner()) {
            return redirect()->route('owner.stock-transfers.show', $transferId);
        } elseif ($user->isKasir()) {
            return redirect()->route('kasir.stock-request');
        }

        return redirect()->back();
    }

    /**
     * Mark all notifications of the authenticated user as read.
     */
    public function readAll()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return redirect()->back()->with('success', 'Semua notifikasi telah ditandai dibaca.');
    }

    /**
     * Get the count of unread notifications and low stock items.
     */
    public function getUnreadCount()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['unread_count' => 0, 'low_stock_count' => 0, 'total' => 0]);
        }

        $unreadCount = $user->unreadNotifications()->count();

        // Query dasar low stock
        $lowStockQuery = \App\Models\ProductStock::with(['product', 'location'])
            ->where('stock', '<=', 10)
            ->where('stock', '>', 0);

        if ($user->isAdmin() || $user->isOwner()) {
            $lowStockQuery->whereHas('location', function($q) {
                $q->active();
            });
        } else {
            $myLocation = \App\Models\StockLocation::findByBranchName($user->branch);
            if ($myLocation) {
                $lowStockQuery->where('location_id', $myLocation->id);
            } else {
                $lowStockQuery->whereRaw('1 = 0');
            }
        }

        $lowStockCount = $lowStockQuery->count();

        return response()->json([
            'unread_count' => $unreadCount,
            'low_stock_count' => $lowStockCount,
            'total' => $unreadCount + $lowStockCount
        ]);
    }
}
