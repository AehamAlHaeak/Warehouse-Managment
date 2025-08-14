<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class load_replaced extends Notification
{
    use Queueable;

    public $load;
    public $cause;
    public $quantity;
    public function __construct($load,$cause,$quantity)
    {
        $this->load = $load;
        $this->cause = $cause;
        $this->quantity = $quantity;
    }
public function via(object $notifiable): array
    {
      return ['database', 'broadcast'];
    }

   
    public function toArray(object $notifiable): array
    {    
       
        $vehicle=$this->load->vehicle;
        $product=$vehicle->product;

        return [
            "msg"=>"the load will be replced because its $this->cause Out of range for 2 hours straight ",
            "load_id"=>$this->load->id,
            "previos_vehicle_id"=>$vehicle->id,
            "product"=>$product->name,
            "quantity"=>$this->quantity
           
        ];
    }
}
