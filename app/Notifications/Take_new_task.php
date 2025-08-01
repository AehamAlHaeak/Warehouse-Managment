<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Take_new_task extends Notification
{
    use Queueable;

    protected $task;
    public function __construct($task)
    {
        $this->task = $task;
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
            "msg"=>"you have new task",
            "task"=>$this->task
        ];
    }
}
