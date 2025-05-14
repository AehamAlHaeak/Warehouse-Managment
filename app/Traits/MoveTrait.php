<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait MoveTrait
{
           public function transferContainersTo($containers, Model $newDestination)
    {
        foreach ($containers as $container) {
            // change to be destiantion (new destination)
            $container->destination_type = get_class($newDestination);
            $container->destination_id = $newDestination->id;

            // remove the source
            $container->source_type = null;
            $container->source_id = null;

            $container->save();
        }
    }
}
