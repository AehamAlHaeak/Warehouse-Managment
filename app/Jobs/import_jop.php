<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class import_jop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $import_jop;
    public $products;
    public $vehicles;
    public $cargos;
    public function __construct($import_jop,$products,$vehicles,$cargos)
    {
        
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        
    }
}
