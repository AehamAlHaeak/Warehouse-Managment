<?php


use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('notifications.user.{id}', function ($user, $id) {
    return $user instanceof \App\Models\User && (int) $user->id === (int) $id;
}, ['guards' => ['web']]);

Broadcast::channel('notifications.employe.{id}', function ($employee, $id) {
    return $employee instanceof \App\Models\Employe && (int) $employee->id === (int) $id;
}, ['guards' => ['employee']]);
Broadcast::channel('test.public.channel', function () {
    return true; 
});