\<?php

use App\Models\User;
use App\Models\Organisation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;

use function Pest\Laravel\postJson;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    Artisan::call('passport:optimized-install');
});

/**
 * Targeted Tests
 */

/**
 * Authentication Tests
 */
describe('Authentication Tests', function () {

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
});

/**
 * Token Generation Tests
 */
describe('Token Generation Tests', function () {

    it('should generate token with correct user details', function () {
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = postJson('/auth/login', [
            'email' => 'john.doe@example.com',
            'password' => 'password',
        ]);

        $token = $response->json('data.accessToken');

        $decodedToken = json_decode(base64_decode(explode('.', $token)[1]));

        expect($decodedToken->sub)->toBe($user->userId);
    });
});

/**
 * Organisation Data Access Tests
 */
describe('Organisation Data Access Tests', function () {

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

    it('should allow user to see their own organisations', function () {
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'password' => Hash::make('password'),
        ]);

        Passport::actingAs($user);

        $organisation = Organisation::factory()->create();
        $user->organisations()->attach($organisation->orgId);

        $response = getJson('/api/organisations/' . $organisation->orgId, [
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,
        ]);

        $response->assertStatus(200);
    });
});

/**
 * End-to-End Tests for Register Endpoint
 */
describe('End-to-End Tests for Register Endpoint', function () {

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
});



/**
 * Endpoint Tests
 */

describe('Endpoint Tests', function () {

    /**
     * POST /api/organisations
     */
    describe('POST /api/organisations', function () {

        it('should create an organisation', function () {
            $user = User::factory()->create();
            Passport::actingAs($user);

            $response = postJson('/api/organisations', [
                'name' => 'New Organisation',
            ]);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'orgId',
                        'name',
                    ],
                ]);

            assertDatabaseHas('organisations', [
                'name' => 'New Organisation',
            ]);
        });

        it('should fail with invalid data', function () {
            $user = User::factory()->create();
            Passport::actingAs($user);

            $response = postJson('/api/organisations', [
                // 'name' => 'New Organisation', // Missing
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'errors' => [
                        [
                            'field' => 'name',
                            'message' => 'The name field is required.',
                        ],
                    ],
                ]);
        });
    });

    /**
     * GET /api/organisations/{orgId}
     */
    describe('GET /api/organisations/{orgId}', function () {

        it('should get an organisation', function () {
            $user = User::factory()->create();
            Passport::actingAs($user);

            $organisation = Organisation::factory()->create();
            $user->organisations()->attach($organisation->orgId);

            $response = getJson('/api/organisations/' . $organisation->orgId);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'orgId',
                        'name',
                    ],
                ]);
        });

        it('should return 404 for non-existent organisation', function () {
            $user = User::factory()->create();
            Passport::actingAs($user);

            $response = getJson('/api/organisations/99999');

            $response->assertStatus(404)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Organisation not found',
                ]);
        });

        it('should return 403 for unauthorized access', function () {
            $user = User::factory()->create();
            $anotherUser = User::factory()->create();
            Passport::actingAs($anotherUser);

            $organisation = Organisation::factory()->create();
            $user->organisations()->attach($organisation->orgId);

            $response = getJson('/api/organisations/' . $organisation->orgId);

            $response->assertStatus(403)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ]);
        });
    });

});
