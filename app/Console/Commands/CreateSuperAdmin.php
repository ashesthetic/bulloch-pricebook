<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CreateSuperAdmin extends Command
{
    protected $signature = 'user:create-super-admin';
    protected $description = 'Interactively create a super_admin user';

    public function handle(): int
    {
        $name     = $this->ask('Name');
        $email    = $this->ask('Email');
        $password = $this->secret('Password');
        $confirm  = $this->secret('Confirm password');

        if ($password !== $confirm) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            if (! $this->confirm("A user with email {$email} already exists. Assign super_admin role to them?")) {
                return self::FAILURE;
            }
            $user = User::where('email', $email)->first();
        } else {
            $user = User::create([
                'name'     => $name,
                'email'    => $email,
                'password' => bcrypt($password),
            ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'super_admin']);
        $user->syncRoles(['super_admin']);

        $this->info("Super admin '{$user->email}' created/updated successfully.");

        return self::SUCCESS;
    }
}
