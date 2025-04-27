<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function FunctionName()
    {
        return "hellow";
    }

    public function Majd()
    {
        return "hellow Majd";

    public function FunctionName(){
       return "hellow amjad"; 

    }
    public function amjad(){
        return "hello Ayham";
    }
}
