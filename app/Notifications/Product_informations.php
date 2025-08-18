<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Product_informations extends Notification
{
    use Queueable;

    protected $product;
    public function __construct($product)
    {
       
        $this->product=$product;
    }

    
    public function via(object $notifiable): array
    {
           return ['database', 'broadcast'];
    }

    
    public function toArray(object $notifiable): array
    {
        return [
            "msg"=>"the periodic informations about product",
           
            "product"=>$this->product,
           
        ];
    }
}
