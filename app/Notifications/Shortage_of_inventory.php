<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Shortage_of_inventory extends Notification
{
    use Queueable;

    protected $place;
    protected $product;
    public function __construct($place,$product)
    {
        $this->place=$place;
        $this->product=$product;
    }

    
    public function via(object $notifiable): array
    {
           return ['database', 'broadcast'];
    }

    
    public function toArray(object $notifiable): array
    {
        return [
            "msg"=>"the place inventory is low  ",
            "place"=>$this->place,
            "product"=>$this->product

        ];
    }
}
