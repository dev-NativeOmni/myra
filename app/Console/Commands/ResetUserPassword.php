<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

#[Signature('app:reset-password {username : Username akun yang password-nya direset}')]
#[Description('Reset password akun mana pun (termasuk Super Admin) dan putuskan semua sesi login lamanya')]
class ResetUserPassword extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::withoutGlobalScopes()->where('username', $this->argument('username'))->first();

        if (! $user) {
            $this->error("Akun @{$this->argument('username')} tidak ditemukan.");

            return self::FAILURE;
        }

        $password = $this->secret('Password baru (minimal 8 karakter)');
        $passwordConfirmation = $this->secret('Ulangi password baru');

        $validator = Validator::make(
            ['password' => $password, 'password_confirmation' => $passwordConfirmation],
            ['password' => 'required|string|min:8|confirmed'],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user->forceFill([
            'password' => $password,
            'remember_token' => null,
        ])->save();

        // Sign the account out everywhere, so a leaked old password or session stops working.
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $this->info("Password @{$user->username} ({$user->role_label}) berhasil direset. Semua sesi login lamanya telah diputus.");

        return self::SUCCESS;
    }
}
