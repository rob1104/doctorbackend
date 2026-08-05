<?php

use Illuminate\Support\Facades\Broadcast;

// Configurar las rutas de Broadcasting para usar Sanctum (Bearer Token)
Broadcast::routes(['middleware' => ['auth:sanctum']]);

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('admin.notifications', function ($user) {
    // Solo permitir acceso si el usuario es admin o asistente
    return in_array($user->role, ['admin', 'assistant']);
});
