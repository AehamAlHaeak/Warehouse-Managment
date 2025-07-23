<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

   

    public function Majd()
    {
        return "hellow Majd";
    }
    public function Amjad(){
       return "hellow amjad"; 

    }
    public function Aeham(){
        return "hello Ayham";
    }
}
