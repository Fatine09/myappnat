<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $product;
    protected $criticalLevel;

    /**
     * Create a new notification instance.
     *
     * @param Product $product
     * @param bool $criticalLevel
     * @return void
     */
    public function __construct(Product $product, bool $criticalLevel = false)
    {
        $this->product = $product;
        $this->criticalLevel = $criticalLevel;
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
        $url = url('/products/' . $this->product->id . '/edit');
        
        $mailMessage = (new MailMessage);
        
        if ($this->criticalLevel) {
            $mailMessage->subject('URGENT: Stock critique pour ' . $this->product->name)
                        ->greeting('Attention ' . $notifiable->name . ',')
                        ->line('Le stock du produit "' . $this->product->name . '" est critique !')
                        ->line('Stock actuel: ' . $this->product->stock . ' unités')
                        ->line('Seuil critique: ' . ($this->product->stock_threshold / 2) . ' unités');
        } else {
            $mailMessage->subject('Notification de stock bas: ' . $this->product->name)
                        ->greeting('Bonjour ' . $notifiable->name . ',')
                        ->line('Le stock du produit "' . $this->product->name . '" est bas.')
                        ->line('Stock actuel: ' . $this->product->stock . ' unités')
                        ->line('Seuil d\'alerte: ' . $this->product->stock_threshold . ' unités');
        }
        
        return $mailMessage->action('Gérer le stock du produit', $url)
                          ->line('Veuillez réapprovisionner le stock dès que possible pour éviter les ruptures de stock.');
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
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'stock' => $this->product->stock,
            'threshold' => $this->product->stock_threshold,
            'critical' => $this->criticalLevel,
            'message' => $this->criticalLevel 
                ? 'URGENT: Stock critique pour ' . $this->product->name . ': ' . $this->product->stock . ' unités restantes.'
                : 'Stock bas pour ' . $this->product->name . ': ' . $this->product->stock . ' unités restantes.',
        ];
    }
}