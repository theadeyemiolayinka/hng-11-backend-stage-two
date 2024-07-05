<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

use function Pest\Laravel\postJson;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

uses(RefreshDatabase::class);


it('should register user successfully', function () {

    Artisan::call('passport:optimized-install');

    $response = postJson('/auth/register', [
        'firstName' => 'John',
        'lastName' => 'Doe',
        'email' => 'john.doe@example.com',
        'password' => 'password',
        'phone' => '1234567890',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'accessToken',
                'user' => [
                    'userId',
                    'firstName',
                    'lastName',
                    'email',
                    'phone',
                ],
            ],
        ]);

    assertDatabaseHas('users', [
        'email' => 'john.doe@example.com',
    ]);
});

it('should create default organisation on register', function () {

    Artisan::call('passport:optimized-install');

    postJson('/auth/register', [
        'firstName' => 'John',
        'lastName' => 'Doe',
        'email' => 'john.doe@example.com',
        'password' => 'password',
        'phone' => '1234567890',
    ]);

    assertDatabaseHas('organisations', [
        'name' => "John's Organisation",
    ]);
});

it('should fail login with invalid data', function () {
    $user = User::factory()->create([
        'email' => 'john.doe@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = postJson('/auth/login', [
        'email' => 'john.doe@example.com',
        'password' => 'not-password',
    ]);

    $response->assertStatus(401)
        ->assertJsonStructure([
            'status',
            'message',
            'statusCode',
        ]);
});

it('should log the user in successfully', function () {

    Artisan::call('passport:optimized-install');
    
    $user = User::factory()->create([
        'email' => 'john.doe@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = postJson('/auth/login', [
        'email' => 'john.doe@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'accessToken',
                'user' => [
                    'userId',
                    'firstName',
                    'lastName',
                    'email',
                    'phone',
                ],
            ],
        ]);
});

it('should fail registration if required fields are missing', function () {
    $response = postJson('/auth/register', [
        'firstName' => 'John',
        // 'lastName' => 'Doe', // Missing
        'email' => 'john.doe@example.com',
        'password' => 'password',
        'phone' => '1234567890',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'errors' => [
                [
                    'field' => 'lastName',
                    'message' => 'The last name field is required.',
                ],
            ],
        ]);
});

it('should fail registration if there is duplicate email or userId', function () {
    User::factory()->create([
        'email' => 'john.doe@example.com',
    ]);

    $response = postJson('/auth/register', [
        'firstName' => 'Jane',
        'lastName' => 'Doe',
        'email' => 'john.doe@example.com',
        'password' => 'password',
        'phone' => '0987654321',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'errors' => [
                [
                    'field' => 'email',
                    'message' => 'The email has already been taken.',
                ],
            ],
        ]);
});
