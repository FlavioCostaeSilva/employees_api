<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Employee;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ManagerControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function it_can_register_a_new_manager()
    {
        // Arrange
        $managerData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // Act
        $response = $this->postJson('/api/register', $managerData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token'
                ]
            ])
            ->assertJsonPath('data.user.name', 'John Doe')
            ->assertJsonPath('data.user.email', 'john@example.com');

        $this->assertDatabaseHas('managers', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Verifica se a senha foi hasheada
        $manager = Manager::where('email', 'john@example.com')->first();
        $this->assertTrue(Hash::check('password123', $manager->password));
    }

    /** @test */
    public function it_validates_required_fields_on_registration()
    {
        // Act
        $response = $this->postJson('/api/register', []);

        // Assert
        $response->assertStatus(400)
            ->assertJson(['message' => 'The name field is required. (and 2 more errors)']);
    }

    /** @test */
    public function it_validates_email_format_on_registration()
    {
        // Arrange
        $managerData = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // Act
        $response = $this->postJson('/api/register', $managerData);

        // Assert
        $response->assertStatus(400)
            ->assertJson(['message' => 'The email must be a valid email address.']);
    }

    /** @test */
    public function it_validates_unique_email_on_registration()
    {
        // Arrange
        Manager::factory()->create(['email' => 'existing@example.com']);

        $managerData = [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // Act
        $response = $this->postJson('/api/register', $managerData);

        // Assert
        $response->assertStatus(400)
            ->assertJson(['message' => "The email has already been taken."]);
    }

    /** @test */
    public function it_validates_password_confirmation_on_registration()
    {
        // Arrange
        $managerData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
        ];

        // Act
        $response = $this->postJson('/api/register', $managerData);

        // Assert
        $response->assertStatus(400)
            ->assertJson(['message' => 'The password confirmation does not match.']);
    }

    /** @test */
    public function it_validates_minimum_password_length_on_registration()
    {
        // Arrange
        $managerData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ];

        // Act
        $response = $this->postJson('/api/register', $managerData);

        // Assert
        $response->assertStatus(400)
            ->assertJson(['message' => 'The password must be at least 8 characters.']);
    }

    /** @test */
    public function it_can_login_with_valid_credentials()
    {
        // Arrange
        $manager = Manager::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $credentials = [
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        // Act
        $response = $this->postJson('/api/login', $credentials);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'token_type',
                    'manager' => ['id', 'name', 'email']
                ]
            ])
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.manager.id', $manager->id)
            ->assertJsonPath('data.manager.email', 'john@example.com');

        // Verifica se o token foi criado
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => Manager::class,
            'tokenable_id' => $manager->id,
        ]);
    }

    /** @test */
    public function it_cannot_login_with_invalid_email()
    {
        // Arrange
        Manager::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $credentials = [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ];

        // Act
        $response = $this->postJson('/api/login', $credentials);

        // Assert
        $response->assertStatus(400)
            ->assertJson(['message' => 'Wrong credentials']);
    }

    /** @test */
    public function it_cannot_login_with_invalid_password()
    {
        // Arrange
        Manager::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $credentials = [
            'email' => 'john@example.com',
            'password' => 'wrong_password',
        ];

        // Act
        $response = $this->postJson('/api/login', $credentials);

        // Assert
        $response->assertStatus(400)
            ->assertJson(['message' => 'Wrong credentials']);
    }

    /** @test */
    public function it_validates_required_fields_on_login()
    {
        // Act
        $response = $this->postJson('/api/login', []);

        // Assert
        $response->assertStatus(400)
            ->assertJson(json_decode('{"data":null,"message":"The email field is required. (and 1 more error)"}', true));
    }

    /** @test */
    public function it_validates_email_format_on_login()
    {
        // Arrange
        $credentials = [
            'email' => 'not-an-email',
            'password' => 'password123',
        ];

        // Act
        $response = $this->postJson('/api/login', $credentials);

        // Assert
        $response->assertStatus(400)
            ->assertJson(['message' => 'The email must be a valid email address.']);
    }

    /** @test */
    public function it_requires_authentication_to_logout()
    {
        // Act
        $response = $this->postJson('/api/logout');

        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_get_authenticated_manager_profile()
    {
        // Arrange
        $manager = Manager::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        Sanctum::actingAs($manager);

        // Act
        $response = $this->getJson('/api/me');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'manager' => [
                        'id',
                        'name',
                        'email',
                        'email_verified_at',
                        'created_at',
                        'updated_at',
                    ],
                    'total_employees'
                ]
            ])
            ->assertJsonPath('data.manager.id', $manager->id)
            ->assertJsonPath('data.manager.name', 'John Doe')
            ->assertJsonPath('data.manager.email', 'john@example.com')
            ->assertJsonPath('data.total_employees', 0);
    }

    /** @test */
    public function it_returns_correct_employee_count_in_profile()
    {
        // Arrange
        $manager = Manager::factory()->create();

        // Criar 5 funcionários para este manager
        Employee::factory()->count(5)->create([
            'manager_id' => $manager->id
        ]);

        // Criar funcionários de outro manager (não devem contar)
        $otherManager = Manager::factory()->create();
        Employee::factory()->count(3)->create([
            'manager_id' => $otherManager->id
        ]);

        Sanctum::actingAs($manager);

        // Act
        $response = $this->getJson('/api/me');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.total_employees', 5);
    }

    /** @test */
    public function it_requires_authentication_to_get_profile()
    {
        // Act
        $response = $this->getJson('/api/me');

        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function it_does_not_expose_password_in_registration_response()
    {
        // Arrange
        $managerData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // Act
        $response = $this->postJson('/api/register', $managerData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonMissing(['password']);
    }

    /** @test */
    public function it_does_not_expose_password_in_login_response()
    {
        // Arrange
        $manager = Manager::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $credentials = [
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        // Act
        $response = $this->postJson('/api/login', $credentials);

        // Assert
        $response->assertStatus(200)
            ->assertJsonMissing(['password']);
    }

    /** @test */
    public function it_does_not_expose_password_in_profile_response()
    {
        // Arrange
        $manager = Manager::factory()->create();
        Sanctum::actingAs($manager);

        // Act
        $response = $this->getJson('/api/me');

        // Assert
        $response->assertStatus(200)
            ->assertJsonMissing(['password']);
    }
}
