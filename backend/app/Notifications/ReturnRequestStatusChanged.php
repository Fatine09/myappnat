<?php

namespace App\Notifications;

use App\Models\ReturnRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReturnRequestStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    protected $returnRequest;
    protected $oldStatus;

    /**
     * Create a new notification instance.
     *
     * @param ReturnRequest $returnRequest
     * @param string $oldStatus
     * @return void
     */
    public function __construct(ReturnRequest $returnRequest, string $oldStatus)
    {
        $this->returnRequest = $returnRequest;
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
        $url = url('/return-requests/' . $this->returnRequest->id);
        
        $mailMessage = (new MailMessage)
            ->subject('Mise à jour de votre demande de retour #' . $this->returnRequest->reference_number)
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Votre demande de retour a changé de statut.');
            
        switch($this->returnRequest->status) {
            case 'approved':
                $mailMessage->line('Votre demande de retour a été approuvée.')
                           ->line('Vous pouvez maintenant préparer votre colis pour le retour.');
                break;
            case 'processing':
                $mailMessage->line('Votre demande de retour est en cours de traitement.');
                break;
            case 'completed':
                $mailMessage->line('Votre retour a été traité avec succès.')
                           ->line('Le remboursement sera effectué selon la méthode de paiement initiale.');
                break;
            case 'rejected':
                $mailMessage->line('Votre demande de retour a été refusée.')
                           ->line('Pour plus d\'informations, veuillez consulter les détails de votre demande.');
                break;
            default:
                $mailMessage->line('Nouveau statut: ' . $this->returnRequest->status);
        }
            
        return $mailMessage
            ->action('Voir les détails du retour', $url)
            ->line('Merci de votre compréhension.');
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
            'order_id' => $this->returnRequest->order_id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->returnRequest->status,
            'message' => 'Le statut de votre demande de retour #' . $this->returnRequest->reference_number . ' a changé de "' . $this->oldStatus . '" à "' . $this->returnRequest->status . '".',
        ];
    }
}