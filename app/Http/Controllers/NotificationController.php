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
}
