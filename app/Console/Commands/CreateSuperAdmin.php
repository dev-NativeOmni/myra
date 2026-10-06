<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('app:create-super-admin {username : Username untuk login} {--name=Super Admin : Nama tampilan} {--email= : Email (opsional)}')]
#[Description('Buat satu-satunya akun Super Admin platform (tidak terikat ke lembaga mana pun)')]
class CreateSuperAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $existing = User::withoutGlobalScopes()->where('role', User::ROLE_SUPER_ADMIN)->first();

        if ($existing) {
            $this->error("Platform sudah memiliki Super Admin (@{$existing->username}). Hanya boleh ada satu Super Admin.");

            return self::FAILURE;
        }

        $password = $this->secret('Password (minimal 8 karakter)');
        $passwordConfirmation = $this->secret('Ulangi password');

        $validator = Validator::make([
            'name' => $this->option('name'),
            'username' => $this->argument('username'),
            'email' => $this->option('email'),
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|alpha_dash|unique:users,username',
            'email' => 'nullable|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            ...$validator->safe()->except('password_confirmation'),
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->info("Super Admin @{$user->username} berhasil dibuat.");

        return self::SUCCESS;
    }
}
