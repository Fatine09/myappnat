<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    protected $payment;
    protected $order;

    /**
     * Create a new notification instance.
     *
     * @param Payment $payment
     * @return void
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
        $this->order = $payment->order;
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
            ->subject('Paiement reçu pour la commande #' . $this->order->order_number)
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Nous avons bien reçu votre paiement pour la commande #' . $this->order->order_number . '.')
            ->line('Montant reçu: ' . number_format($this->payment->amount, 2) . ' €')
            ->line('Date de paiement: ' . $this->payment->created_at->format('d/m/Y H:i'))
            ->line('Méthode de paiement: ' . $this->payment->payment_method)
            ->action('Voir les détails de la commande', $url)
            ->line('Nous vous remercions pour votre achat et préparons votre commande dans les plus brefs délais.');
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
            'payment_id' => $this->payment->id,
            'amount' => $this->payment->amount,
            'payment_method' => $this->payment->payment_method,
            'message' => 'Paiement reçu pour la commande #' . $this->order->order_number . ' d\'un montant de ' . number_format($this->payment->amount, 2) . ' €.',
        ];
    }
}