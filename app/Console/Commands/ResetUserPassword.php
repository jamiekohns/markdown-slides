<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetUserPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:reset-password
                            {email : The email of the user whose password should be reset}
                            {--password= : The new password (if not provided, a random one will be generated)}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Reset a user password';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $newPassword = $this->option('password');

        // Find the user
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return self::FAILURE;
        }

        // Generate password if not provided
        if (!$newPassword) {
            $newPassword = bin2hex(random_bytes(8));
            $this->info("Generated password: {$newPassword}");
        }

        // Update the password
        $user->password = Hash::make($newPassword);
        $user->save();

        $this->info("Password for user '{$email}' has been reset successfully.");
        
        if ($this->option('password') === null) {
            $this->line("New password: <fg=yellow>{$newPassword}</>");
        }

        return self::SUCCESS;
    }
}
