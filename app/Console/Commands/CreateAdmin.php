<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Validation\Rules;
use Validator;

class CreateAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin';

    /**
     * This command creates an admin user.
     *
     * @var string
     */
    protected $description = 'Create an Admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->ask('Name');

        $validator = Validator::make(
            compact('name'),
            ['name' => ['required', 'string', 'max:255']]
        );

        if ($validator->fails()) {
            $errors = $validator->errors();
            $this->error($errors->first('name'));
            return 1;
        }

        $email = $this->ask('Email');

        $validator = Validator::make(
            compact('email'),
            [
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    'unique:' . User::class,
                ]
            ]
        );

        if ($validator->fails()) {
            $errors = $validator->errors();
            $this->error($errors->first('email'));
            return 1;
        }

        $password = $this->secret('Password');
        $password_confirmation = $this->secret('Confirm password');

        $validator = Validator::make(
            compact('password', 'password_confirmation'),
            ['password' => ['required', 'confirmed', Rules\Password::defaults()]]
        );

        if ($validator->fails()) {
            $errors = $validator->errors();
            $this->error($errors->first('password'));
            return 1;
        }

        if ($this->confirm('Do you wish to continue?', true)) {
            $password = bcrypt($password);
            $email_verified_at = now();
            $role = UserRole::ADMIN;

            try {
                $user = User::create(
                    compact(
                        'name',
                        'email',
                        'password',
                        'email_verified_at',
                        'role',
                    )
                );
            } catch (\Exception $e) {
                $this->error($e->getMessage());
                return 1;
            }
        }

        $this->info('Successfully created admin.');
        $this->table(
            ['Name', 'Email'],
            [compact('name', 'email')],
        );
    }
}
