<?php

namespace Tests\Feature\Console\Commands;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_console_command(): void
    {
        $this->artisan('make:admin')
            ->expectsQuestion('Name', 'Admin')
            ->expectsQuestion('Email', 'test@example.com')
            ->expectsQuestion('Password', 'password')
            ->expectsQuestion('Confirm password', 'password')
            ->expectsConfirmation('Do you wish to continue?', 'yes')
            ->expectsOutput('Successfully created admin.')
            ->expectsTable([
                'Name',
                'Email',
            ], [
                ['Admin', 'test@example.com'],
            ])
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_console_command_name_validation_error(): void
    {
        $this->artisan('make:admin')
            ->expectsQuestion('Name', '')
            ->expectsOutput('The name field is required.')
            ->assertExitCode(1);
    }

    public function test_console_command_email_validation_error(): void
    {
        User::factory()->state(['email' => 'exists@example.com'])->create();
        $this->artisan('make:admin')
            ->expectsQuestion('Name', 'Admin')
            ->expectsQuestion('Email', 'exists@example.com')
            ->expectsOutput('The email has already been taken.')
            ->assertExitCode(1);
    }
}
