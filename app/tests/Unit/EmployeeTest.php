<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\Manager;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created()
    {
        $manager = Manager::factory()->create();

        $employee = Employee::factory()->forManager($manager)->create([
            'name' => 'João Silva',
            'email' => 'joao@example.com',
        ]);

        $this->assertInstanceOf(Employee::class, $employee);
        $this->assertEquals('João Silva', $employee->name);
        $this->assertEquals('joao@example.com', $employee->email);
        $this->assertDatabaseHas('employees', [
            'email' => 'joao@example.com',
        ]);
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $employee = new Employee();

        $fillable = $employee->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('cpf', $fillable);
        $this->assertContains('city', $fillable);
        $this->assertContains('state', $fillable);
        $this->assertContains('manager_id', $fillable);
    }

    /** @test */
    public function it_requires_unique_email()
    {
        $manager = Manager::factory()->create();
        $email = 'teste@example.com';

        Employee::factory()->forManager($manager)->create(['email' => $email]);

        $this->expectException(QueryException::class);

        Employee::factory()->forManager($manager)->create(['email' => $email]);
    }

    /** @test */
    public function it_requires_unique_cpf()
    {
        $manager = Manager::factory()->create();
        $cpf = '12345678901';

        Employee::factory()->forManager($manager)->create(['cpf' => $cpf]);

        $this->expectException(QueryException::class);

        Employee::factory()->forManager($manager)->create(['cpf' => $cpf]);
    }

    /** @test */
    public function it_belongs_to_a_manager()
    {
        $manager = Manager::factory()->create();
        $employee = Employee::factory()->forManager($manager)->create();

        $this->assertInstanceOf(Manager::class, $employee->manager);
        $this->assertEquals($manager->id, $employee->manager->id);
    }

    /** @test */
    public function it_has_belongs_to_relationship_with_manager()
    {
        $employee = new Employee();

        $this->assertInstanceOf(
            BelongsTo::class,
            $employee->manager()
        );
    }

    /** @test */
    public function it_requires_manager_id()
    {
        $this->expectException(QueryException::class);

        Employee::factory()->create(['manager_id' => null]);
    }

    /** @test */
    public function it_accepts_only_two_characters_for_state()
    {
        $manager = Manager::factory()->create();

        $employee = Employee::factory()->forManager($manager)->create([
            'state' => 'DF',
        ]);

        $this->assertEquals('DF', $employee->state);
        $this->assertEquals(2, strlen($employee->state));
    }

    /** @test */
    public function it_has_formatted_cpf_accessor()
    {
        $manager = Manager::factory()->create();

        $employee = Employee::factory()->forManager($manager)->create([
            'cpf' => '12345678901',
        ]);

        $this->assertEquals('123.456.789-01', $employee->formatted_cpf);
    }

    /** @test */
    public function multiple_employees_can_have_same_manager()
    {
        $manager = Manager::factory()->create();

        $employee1 = Employee::factory()->forManager($manager)->create();
        $employee2 = Employee::factory()->forManager($manager)->create();

        $this->assertEquals($manager->id, $employee1->manager_id);
        $this->assertEquals($manager->id, $employee2->manager_id);
        $this->assertEquals(2, $manager->employees()->count());
    }

    /** @test */
    public function it_can_update_data()
    {
        $manager = Manager::factory()->create();

        $employee = Employee::factory()->forManager($manager)->create([
            'name' => 'Original Name',
            'city' => 'Recife',
        ]);

        $employee->update([
            'name' => 'Updated Name',
            'city' => 'Olinda',
        ]);

        $this->assertEquals('Updated Name', $employee->fresh()->name);
        $this->assertEquals('Olinda', $employee->fresh()->city);
    }

    /** @test */
    public function it_can_be_deleted()
    {
        $manager = Manager::factory()->create();
        $employee = Employee::factory()->forManager($manager)->create();

        $employeeId = $employee->id;
        $employee->delete();

        $this->assertDatabaseMissing('employees', ['id' => $employeeId]);
    }

    /** @test */
    public function it_creates_timestamps_automatically()
    {
        $manager = Manager::factory()->create();
        $employee = Employee::factory()->forManager($manager)->create();

        $this->assertNotNull($employee->created_at);
        $this->assertNotNull($employee->updated_at);
        $this->assertInstanceOf(Carbon::class, $employee->created_at);
    }

    /** @test */
    public function it_can_be_created_from_sao_paulo()
    {
        $manager = Manager::factory()->create();
        $employee = Employee::factory()->forManager($manager)->fromSaoPaulo()->create();

        $this->assertEquals('São Paulo', $employee->city);
        $this->assertEquals('SP', $employee->state);
    }

    /** @test */
    public function it_can_be_created_from_rio()
    {
        $manager = Manager::factory()->create();
        $employee = Employee::factory()->forManager($manager)->fromRio()->create();

        $this->assertEquals('Rio de Janeiro', $employee->city);
        $this->assertEquals('RJ', $employee->state);
    }

    /** @test */
    public function factory_generates_valid_cpf()
    {
        $manager = Manager::factory()->create();
        $employee = Employee::factory()->forManager($manager)->create();

        $this->assertEquals(11, strlen($employee->cpf));
        $this->assertMatchesRegularExpression('/^\d{11}$/', $employee->cpf);
    }

    /** @test */
    public function it_can_create_multiple_employees()
    {
        $manager = Manager::factory()->create();

        $employees = Employee::factory()
            ->forManager($manager)
            ->count(5)
            ->create();

        $this->assertCount(5, $employees);
        $this->assertEquals(5, $manager->employees()->count());
    }
}
