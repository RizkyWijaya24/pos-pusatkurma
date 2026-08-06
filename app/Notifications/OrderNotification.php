<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderNotification extends Notification
{
    use Queueable;

    private Order $order;
    private string $actionType; // created, paid, failed

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, string $actionType = 'created')
    {
        $this->order = $order;
        $this->actionType = $actionType;
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
            'order_id'    => $this->order->id,
            'order_code'  => $this->order->order_code,
            'status'      => $this->order->payment_status,
            'action_type' => $this->actionType,
            'message'     => $this->buildMessage(),
        ];
    }

    /**
     * Build the notification message.
     */
    private function buildMessage(): string
    {
        return match ($this->actionType) {
            'created' => "Pesanan baru dari {$this->order->customer_name} ({$this->order->order_code}) telah diajukan sebesar Rp " . number_format($this->order->total_amount, 0, ',', '.') . ".",
            'paid'    => "Pesanan {$this->order->order_code} telah DIBAYAR (LUNAS) sebesar Rp " . number_format($this->order->total_amount, 0, ',', '.') . ".",
            'failed'  => "Pesanan {$this->order->order_code} GAGAL/BATAL.",
            default   => "Perubahan status pesanan {$this->order->order_code}.",
        };
    }
}
