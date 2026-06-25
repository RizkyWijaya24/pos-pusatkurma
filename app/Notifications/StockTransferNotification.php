<?php

namespace App\Notifications;

use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StockTransferNotification extends Notification
{
    use Queueable;

    private StockTransfer $transfer;
    private string $actionType; // created, approved, rejected, cancelled
    private User $sender;

    /**
     * Create a new notification instance.
     */
    public function __construct(StockTransfer $transfer, string $actionType, User $sender)
    {
        $this->transfer = $transfer;
        $this->actionType = $actionType;
        $this->sender = $sender;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'transfer_id'   => $this->transfer->id,
            'transfer_code' => $this->transfer->transfer_code,
            'status'        => $this->transfer->status,
            'action_type'   => $this->actionType,
            'sender_name'   => $this->sender->name,
            'message'       => $this->buildMessage(),
        ];
    }

    /**
     * Build the notification message.
     */
    private function buildMessage(): string
    {
        $toBranch = $this->transfer->toLocation?->name ?? 'Cabang';

        return match ($this->actionType) {
            'created'   => "Permintaan transfer stok baru {$this->transfer->transfer_code} diajukan ke {$toBranch} oleh {$this->sender->name}.",
            'approved'  => "Permintaan transfer stok {$this->transfer->transfer_code} ke {$toBranch} telah DISETUJUI oleh {$this->sender->name}.",
            'rejected'  => "Permintaan transfer stok {$this->transfer->transfer_code} ke {$toBranch} telah DITOLAK oleh {$this->sender->name} dengan alasan: \"{$this->transfer->rejection_reason}\".",
            'cancelled' => "Permintaan transfer stok {$this->transfer->transfer_code} telah DIBATALKAN oleh {$this->sender->name}.",
            default     => "Perubahan status transfer stok {$this->transfer->transfer_code}.",
        };
    }
}
