<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class BcryptUserPasswords extends Command
{
    protected $signature = 'bcrypt:user-passwords';
    protected $description = 'Bcrypt the mrd_user_password column in the mrd_user table';

    public function handle()
    {
        // Fetch all users from the mrd_user table
        $users = User::all();

        // Update each user's password to use Bcrypt hash
        foreach ($users as $user) {
            $user->update([
                'mrd_user_password' => Hash::make($user->mrd_user_password),
            ]);
        }

        $this->info('mrd_user_password column updated to use Bcrypt hash.');
    }
}
