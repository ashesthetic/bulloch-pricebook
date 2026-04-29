<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class LogAuthEvents
{
    public function handleLogin(Login $event): void
    {
        $user = $event->user;

        // Filament fires the Login event twice per login — debounce within 10 seconds
        $alreadyLogged = AuditLog::where('user_id', $user->id)
            ->where('action', 'login')
            ->where('created_at', '>=', now()->subSeconds(10))
            ->exists();

        if ($alreadyLogged) {
            return;
        }

        AuditLog::create([
            'user_id'    => $user->id,
            'user_name'  => $user->name,
            'user_role'  => $user->roles->first()?->name,
            'action'     => 'login',
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        $user = $event->user;
        if (! $user) {
            return;
        }
        AuditLog::create([
            'user_id'    => $user->id,
            'user_name'  => $user->name,
            'user_role'  => $user->roles->first()?->name,
            'action'     => 'logout',
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
