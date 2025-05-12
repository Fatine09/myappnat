<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected $product;

    /**
     * Create a new notification instance.
     *
     * @param Product $product
     * @return void
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
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

        return (new MailMessage)
            ->subject('Alerte de stock bas: ' . $this->product->name)
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Le stock du produit "' . $this->product->name . '" est bas.')
            ->line('Stock actuel: ' . $this->product->stock . ' unités')
            ->line('Seuil d\'alerte: ' . $this->product->stock_threshold . ' unités')
            ->action('Gérer le stock du produit', $url)
            ->line('Pensez à réapprovisionner votre stock pour éviter les ruptures.');
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
            'message' => 'Stock bas pour ' . $this->product->name . ': ' . $this->product->stock . ' unités restantes.',
        ];
    }
};