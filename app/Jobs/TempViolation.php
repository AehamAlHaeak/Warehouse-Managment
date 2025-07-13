<?php

namespace App\Jobs;

use App\Models\Violation;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class TempViolation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
       protected $violation_id;


    public function __construct($violation_id)
    {
      $this->violation_id = $violation_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
      
        $violation=Violation::find($this->violation_id);
    }
}
