<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Send_the_order_faild extends Notification
{
    use Queueable;

    protected $order;
    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
      return ['database', 'broadcast'];
    }

    
    public function toArray(object $notifiable): array
    {
        return [
            "msg"=>"the order faild",
            "order"=>$this->order
        ];
    }
}
