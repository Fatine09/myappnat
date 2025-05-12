<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentConfirmation extends Notification implements ShouldQueue
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
            ->subject('Confirmation de paiement pour la commande #' . $this->order->order_number)
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Le paiement pour votre commande a bien été reçu et a été traité avec succès.')
            ->line('Numéro de commande: ' . $this->order->order_number)
            ->line('Référence du paiement: ' . $this->payment->payment_id)
            ->line('Montant: ' . number_format($this->payment->amount, 2) . ' €')
            ->action('Voir les détails de la commande', $url)
            ->line('Merci pour votre confiance!');
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
            'payment_id' => $this->payment->id,
            'amount' => $this->payment->amount,
            'status' => $this->payment->status,
            'message' => 'Le paiement pour votre commande #' . $this->order->order_number . ' a été confirmé.',
        ];
    }
};