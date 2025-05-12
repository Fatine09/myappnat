<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;

    /**
     * Create a new notification instance.
     *
     * @param Order $order
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = url('/orders/' . $this->order->id);

        return (new MailMessage)
            ->subject('Confirmation de votre commande #' . $this->order->order_number)
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Nous vous confirmons que votre commande a bien été reçue.')
            ->line('Numéro de commande: ' . $this->order->order_number)
            ->line('Montant total: ' . number_format($this->order->total_amount, 2) . ' €')
            ->action('Voir les détails de la commande', $url)
            ->line('Merci pour votre achat!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'amount' => $this->order->total_amount,
            'status' => $this->order->status,
            'message' => 'Votre commande #' . $this->order->order_number . ' a été confirmée.',
        ];
    }
};