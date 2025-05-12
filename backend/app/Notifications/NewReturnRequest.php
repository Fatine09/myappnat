<?php

namespace App\Notifications;

use App\Models\ReturnRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReturnRequest extends Notification implements ShouldQueue
{
    use Queueable;

    protected $returnRequest;
    protected $order;

    /**
     * Create a new notification instance.
     *
     * @param ReturnRequest $returnRequest
     * @return void
     */
    public function __construct(ReturnRequest $returnRequest)
    {
        $this->returnRequest = $returnRequest;
        $this->order = $returnRequest->order;
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
        $url = url('/return-requests/' . $this->returnRequest->id);

        return (new MailMessage)
            ->subject('Nouvelle demande de retour #' . $this->returnRequest->reference_number)
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Nous avons bien reçu votre demande de retour pour la commande #' . $this->order->order_number . '.')
            ->line('Référence du retour: ' . $this->returnRequest->reference_number)
            ->line('Date de la demande: ' . $this->returnRequest->created_at->format('d/m/Y H:i'))
            ->line('Raison du retour: ' . $this->returnRequest->reason)
            ->action('Voir les détails du retour', $url)
            ->line('Notre équipe va traiter votre demande dans les meilleurs délais. Vous recevrez une notification dès que le statut de votre demande sera mis à jour.');
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
            'return_request_id' => $this->returnRequest->id,
            'reference_number' => $this->returnRequest->reference_number,
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'reason' => $this->returnRequest->reason,
            'status' => $this->returnRequest->status,
            'message' => 'Votre demande de retour #' . $this->returnRequest->reference_number . ' pour la commande #' . $this->order->order_number . ' a été créée.',
        ];
    }
};