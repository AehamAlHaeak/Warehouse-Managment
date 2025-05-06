<?php

use Tymon\JWTAuth\Facades\JWTAuth;

trait TokenUser
{
    public function token_user($object)
    {
        $claims = [
            'id' => $object->id,
            'name' => $object->name,
            'email' => $object->email,
            'phone_number' => $object->phone_number,
        ];

        $token = JWTAuth::claims($claims)->fromUser($object);
        return $token;
    }
}
