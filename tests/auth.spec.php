<?php

use App\Models\User;
use App\Models\Organisation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Passport;

use function Pest\Laravel\postJson;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    Artisan::call('passport:optimized-install');
});

it('should register user successfully', function () {
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

it('should fail registration if there is duplicate email', function () {
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

// Token generation test
it('should generate token with correct user details', function () {
    $user = User::factory()->create([
        'email' => 'john.doe@example.com',
        'password' => Hash::make('password'),
    ]);

    // Passport::actingAs($user);

    $response = postJson('/auth/login', [
        'email' => 'john.doe@example.com',
        'password' => 'password',
    ]);

    $token = $response->json('data.accessToken');

    $decodedToken = json_decode(base64_decode(explode('.', $token)[1]));

    expect($decodedToken->sub)->toBe($user->userId);
});

// Organisation data access test
it('should not allow user to see other organisations', function () {
    postJson('/auth/register', [
        'firstName' => 'John',
        'lastName' => 'Doe',
        'email' => 'john.doe@example.com',
        'password' => 'password',
        'phone' => '1234567890',
    ]);

    $anotherUser = User::factory()->create([
        'email' => 'john.another@example.com',
        'password' => Hash::make('password'),
    ]);


    $organisation = User::where('email', 'john.doe@example.com')->first()->organisations()->first();

    Passport::actingAs($anotherUser);

    $response = getJson('/api/organisations/' . $organisation->orgId);

    $response->assertStatus(403);
});

// End-to-End tests for the Register Endpoint
it('should register user successfully end-to-end', function () {
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
});

it('should handle validation errors on registration end-to-end', function () {
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

it('should handle database constraints on registration end-to-end', function () {
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
