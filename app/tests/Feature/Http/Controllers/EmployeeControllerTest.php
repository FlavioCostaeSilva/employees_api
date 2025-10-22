<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Employee;
use App\Models\Manager;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Manager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = Manager::factory()->create();
    }

    /** @test */
    public function it_can_list_all_employees_for_authenticated_user()
    {
       Employee::factory()->count(3)->create([
            'manager_id' => $this->manager->id
        ]);

        $otherUser = Manager::factory()->create();
        Employee::factory()->count(2)->create([
            'manager_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->manager)
            ->getJson('/api/employees');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'count',
                    'registers' => [
                        '*' => ['id', 'name', 'manager_id']
                    ]
                ]
            ])
            ->assertJsonPath('data.count', 3);
    }

    /** @test */
    public function it_returns_empty_list_when_user_has_no_employees()
    {
        // Act
        $response = $this->actingAs($this->manager)
            ->getJson('/api/employees');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.count', 0)
            ->assertJsonPath('data.registers', []);
    }

    /** @test */
    public function it_requires_authentication_to_list_employees()
    {
        // Act
        $response = $this->getJson('/api/employees');

        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_create_an_employee()
    {
        // Arrange
        $employeeData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'cpf' => '525.437.410-11',
            'city' => 'Some City',
            'state' => 'TO',
            'manager_id' => $this->manager->id
        ];

        // Act
        $response = $this->actingAs($this->manager)
            ->postJson('/api/employees', $employeeData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'employee' => ['id', 'name', 'email']
                ],
                'message'
            ])
            ->assertJsonPath('message', 'Employee created with success');

        $this->assertDatabaseHas('employees', [
            'name' => $employeeData['name'],
            'email' => $employeeData['email'],
            'manager_id' => $this->manager->id
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_employee()
    {
        // Act
        $response = $this->actingAs($this->manager)
            ->postJson('/api/employees', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);
    }

    /** @test */
    public function it_validates_email_format_when_creating_employee()
    {
        // Arrange
        $employeeData = [
            'name' => $this->faker->name,
            'email' => 'invalid-email-format'
        ];

        // Act
        $response = $this->actingAs($this->manager)
            ->postJson('/api/employees', $employeeData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_can_show_a_specific_employee()
    {
        // Arrange
        $employee = Employee::factory()->create([
            'manager_id' => $this->manager->id
        ]);

        // Act
        $response = $this->actingAs($this->manager)
            ->getJson("/api/employees/{$employee->id}");

        // Assert
        $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['id', 'name', 'manager_id']
        ])
            ->assertJsonPath('data.id', $employee->id)
            ->assertJsonPath('data.name', $employee->name);
    }

    /** @test */
    public function it_returns_404_when_employee_not_found()
    {
        // Act
        $response = $this->actingAs($this->manager)
            ->getJson('/api/employees/999999');

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function it_cannot_show_employee_from_another_user()
    {
        // Arrange
        $otherUser = Manager::factory()->create();
        $employee = Employee::factory()->create([
            'manager_id' => $otherUser->id
        ]);

        // Act
        $response = $this->actingAs($this->manager)
            ->getJson("/api/employees/{$employee->id}");

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_update_an_employee()
    {
        // Arrange
        $employee = Employee::factory()->create([
            'manager_id' => $this->manager->id,
            'name' => 'Old Name'
        ]);

        $updateData = [
            'name' => 'New Name',
            'email' => $this->faker->unique()->safeEmail
        ];

        // Act
        $response = $this->actingAs($this->manager)
            ->putJson("/api/employees/{$employee->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'employee' => ['id', 'name']
                ],
                'message'
            ])
            ->assertJsonPath('message', 'Employee updated with success')
            ->assertJsonPath('data.employee.name', 'New Name');

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'name' => 'New Name'
        ]);
    }

    /** @test */
    public function it_cannot_update_employee_from_another_user()
    {
        // Arrange
        $otherUser = Manager::factory()->create();
        $employee = Employee::factory()->create([
            'manager_id' => $otherUser->id
        ]);

        $updateData = [
            'name' => 'Hacked Name'
        ];

        // Act
        $response = $this->actingAs($this->manager)
            ->putJson("/api/employees/{$employee->id}", $updateData);

        // Assert
        $response->assertStatus(404);

        $this->assertDatabaseMissing('employees', [
            'id' => $employee->id,
            'name' => 'Hacked Name'
        ]);
    }

    /** @test */
    public function it_can_delete_an_employee()
    {
        // Arrange
        $employee = Employee::factory()->create([
            'manager_id' => $this->manager->id,
            'name' => 'John Doe'
        ]);

        // Act
        $response = $this->actingAs($this->manager)
            ->deleteJson("/api/employees/{$employee->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure(['message'])
            ->assertJsonPath('message', 'Employee John Doe deleted with success');

        $this->assertDatabaseMissing('employees', [
            'id' => $employee->id
        ]);
    }

    /** @test */
    public function it_cannot_delete_employee_from_another_user()
    {
        // Arrange
        $otherUser = Manager::factory()->create();
        $employee = Employee::factory()->create([
            'manager_id' => $otherUser->id
        ]);

        // Act
        $response = $this->actingAs($this->manager)
            ->deleteJson("/api/employees/{$employee->id}");

        // Assert
        $response->assertStatus(404);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id
        ]);
    }

    /** @test */
    public function it_returns_404_when_deleting_non_existent_employee()
    {
        // Act
        $response = $this->actingAs($this->manager)
            ->deleteJson('/api/employees/999999');

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function when_delete_an_employee_id_must_be_numeric()
    {
        // Arrange
        Employee::factory()->create([
            'manager_id' => $this->manager->id,
            'name' => 'John Doe'
        ]);

        // Act
        $response = $this->actingAs($this->manager)
            ->deleteJson("/api/employees/INVALID_NOT_NUMERIC_ID");

        // Assert
        $response->assertStatus(400)
            ->assertJsonStructure(['message'])
            ->assertJsonPath('message', 'The id must be a number.');
    }

    /** @test */
    public function when_show_an_employee_id_must_be_numeric()
    {
        // Arrange
        Employee::factory()->create([
            'manager_id' => $this->manager->id,
            'name' => 'John Doe'
        ]);

        // Act
        $response = $this->actingAs($this->manager)
            ->deleteJson("/api/employees/INVALID_NOT_NUMERIC_ID");

        // Assert
        $response->assertStatus(400)
            ->assertJsonStructure(['message'])
            ->assertJsonPath('message', 'The id must be a number.');
    }
}
