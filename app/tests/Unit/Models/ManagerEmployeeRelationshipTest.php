<?php

namespace Tests\Unit\Models;

use App\Models\Employee;
use App\Models\Manager;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerEmployeeRelationshipTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function manager_has_many_employees_relationship()
    {
        $manager = Manager::factory()->create();

        $relation = $manager->employees();

        $this->assertInstanceOf(
            HasMany::class,
            $relation
        );
        $this->assertEquals('manager_id', $relation->getForeignKeyName());
    }

    /** @test */
    public function employee_belongs_to_manager_relationship()
    {
        $employee = new Employee();

        $relation = $employee->manager();

        $this->assertInstanceOf(
            BelongsTo::class,
            $relation
        );
    }

    /** @test */
    public function manager_can_access_their_employees()
    {
        $manager = Manager::factory()->create();

        $employee1 = Employee::factory()->forManager($manager)->create();
        $employee2 = Employee::factory()->forManager($manager)->create();
        $employee3 = Employee::factory()->forManager($manager)->create();

        $employees = $manager->employees;

        $this->assertCount(3, $employees);
        $this->assertTrue($employees->contains($employee1));
        $this->assertTrue($employees->contains($employee2));
        $this->assertTrue($employees->contains($employee3));
    }

    /** @test */
    public function employee_can_access_their_manager()
    {
        $manager = Manager::factory()->create(['name' => 'Manager João']);
        $employee = Employee::factory()->forManager($manager)->create();

        $this->assertEquals('Manager João', $employee->manager->name);
        $this->assertEquals($manager->id, $employee->manager->id);
    }

    /** @test */
    public function different_managers_have_isolated_employees()
    {
        $manager1 = Manager::factory()->create();
        $manager2 = Manager::factory()->create();

        $employee1 = Employee::factory()->forManager($manager1)->create();
        $employee2 = Employee::factory()->forManager($manager2)->create();

        $this->assertCount(1, $manager1->employees);
        $this->assertCount(1, $manager2->employees);

        $this->assertTrue($manager1->employees->contains($employee1));
        $this->assertFalse($manager1->employees->contains($employee2));

        $this->assertTrue($manager2->employees->contains($employee2));
        $this->assertFalse($manager2->employees->contains($employee1));
    }

    /** @test */
    public function deleting_manager_cascade_deletes_employees()
    {
        $manager = Manager::factory()
            ->has(Employee::factory()->count(3))
            ->create();

        $employeeIds = $manager->employees->pluck('id')->toArray();

        $this->assertEquals(3, Employee::count());

        $manager->delete();

        $this->assertEquals(0, Employee::count());

        foreach ($employeeIds as $id) {
            $this->assertDatabaseMissing('employees', ['id' => $id]);
        }
    }

    /** @test */
    public function deleting_employee_does_not_affect_manager()
    {
        $manager = Manager::factory()->create();
        $employee = Employee::factory()->forManager($manager)->create();

        $managerId = $manager->id;
        $employee->delete();

        $this->assertDatabaseHas('managers', ['id' => $managerId]);
        $this->assertEquals(0, $manager->fresh()->employees()->count());
    }

    /** @test */
    public function manager_can_create_employee_via_relationship()
    {
        $manager = Manager::factory()->create();

        $employee = $manager->employees()->create([
            'name' => 'Created Via Relationship',
            'email' => 'viarelationship@example.com',
            'cpf' => '66666666666',
            'city' => 'Porto Alegre',
            'state' => 'RS',
        ]);

        $this->assertInstanceOf(Employee::class, $employee);
        $this->assertEquals($manager->id, $employee->manager_id);
        $this->assertDatabaseHas('employees', [
            'email' => 'viarelationship@example.com',
            'manager_id' => $manager->id,
        ]);
    }

    /** @test */
    public function manager_employees_can_be_counted()
    {
        $manager = Manager::factory()->create();

        $this->assertEquals(0, $manager->employees()->count());

        Employee::factory()->forManager($manager)->create();
        $this->assertEquals(1, $manager->employees()->count());

        Employee::factory()->forManager($manager)->count(2)->create();
        $this->assertEquals(3, $manager->employees()->count());
    }

    /** @test */
    public function manager_can_query_employees_with_where()
    {
        $manager = Manager::factory()->create();

        Employee::factory()->forManager($manager)->fromSaoPaulo()->count(2)->create();
        Employee::factory()->forManager($manager)->fromRio()->create();

        $employeesSP = $manager->employees()->where('city', 'São Paulo')->get();
        $employeesRJ = $manager->employees()->where('city', 'Rio de Janeiro')->get();

        $this->assertCount(2, $employeesSP);
        $this->assertCount(1, $employeesRJ);
    }

    /** @test */
    public function eager_loading_works_correctly()
    {
        $manager = Manager::factory()
            ->has(Employee::factory()->count(3))
            ->create();

        $managerWithEmployees = Manager::with('employees')->find($manager->id);

        $this->assertTrue($managerWithEmployees->relationLoaded('employees'));
        $this->assertCount(3, $managerWithEmployees->employees);
    }

    /** @test */
    public function manager_can_have_zero_employees()
    {
        $manager = Manager::factory()->create();

        $this->assertCount(0, $manager->employees);
        $this->assertEquals(0, $manager->employees()->count());
    }

    /** @test */
    public function foreign_key_manager_id_is_required()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Employee::factory()->create(['manager_id' => null]);
    }

    /** @test */
    public function it_cannot_have_invalid_manager_id()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Employee::factory()->create(['manager_id' => 99999]);
    }

    /** @test */
    public function relationship_maintains_referential_integrity()
    {
        $manager1 = Manager::factory()->create();
        $manager2 = Manager::factory()->create();

        $employee = Employee::factory()->forManager($manager1)->create();

        $employee->update(['manager_id' => $manager2->id]);

        $this->assertEquals($manager2->id, $employee->fresh()->manager_id);
        $this->assertCount(0, $manager1->fresh()->employees);
        $this->assertCount(1, $manager2->fresh()->employees);
    }

    /** @test */
    public function manager_can_be_created_with_employees_using_has()
    {
        $manager = Manager::factory()
            ->has(Employee::factory()->count(5))
            ->create();

        $this->assertCount(5, $manager->employees);
        $this->assertEquals(5, Employee::count());
    }

    /** @test */
    public function employee_factory_automatically_creates_manager()
    {
        $employee = Employee::factory()->create();

        $this->assertInstanceOf(Manager::class, $employee->manager);
        $this->assertNotNull($employee->manager_id);
        $this->assertEquals(1, Manager::count());
    }

    /** @test */
    public function multiple_employees_from_different_cities()
    {
        $manager = Manager::factory()->create();

        Employee::factory()->forManager($manager)->fromSaoPaulo()->create();
        Employee::factory()->forManager($manager)->fromRio()->create();

        $spEmployee = $manager->employees()->where('state', 'SP')->first();
        $rjEmployee = $manager->employees()->where('state', 'RJ')->first();

        $this->assertEquals('SP', $spEmployee->state);
        $this->assertEquals('RJ', $rjEmployee->state);
    }
}
