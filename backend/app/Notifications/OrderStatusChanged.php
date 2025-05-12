<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;
    protected $oldStatus;

    /**
     * Create a new notification instance.
     *
     * @param Order $order
     * @param string $oldStatus
     * @return void
     */
    public function __construct(Order $order, string $oldStatus)
    {
        $this->order = $order;
        $this->oldStatus = $oldStatus;
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
        
        $mailMessage = (new MailMessage)
            ->subject('Mise à jour de votre commande #' . $this->order->order_number)
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Votre commande #' . $this->order->order_number . ' a changé de statut.');
        
        switch($this->order->status) {
            case 'processing':
                $mailMessage->line('Votre commande est en cours de traitement.');
                break;
            case 'shipped':
                $mailMessage->line('Votre commande a été expédiée.');
                break;
            case 'delivered':
                $mailMessage->line('Votre commande a été livrée.');
                break;
            case 'cancelled':
                $mailMessage->line('Votre commande a été annulée.');
                break;
            default:
                $mailMessage->line('Nouveau statut: ' . $this->order->status);
        }
            
        return $mailMessage
            ->action('Voir les détails de la commande', $url)
            ->line('Merci de votre confiance!');
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
            'old_status' => $this->oldStatus,
            'new_status' => $this->order->status,
            'message' => 'Le statut de votre commande #' . $this->order->order_number . ' a changé de "' . $this->oldStatus . '" à "' . $this->order->status . '".',
        ];
    }
}