<?php

namespace Tests\Unit\Models;

use App\Models\Employee;
use App\Models\Manager;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ManagerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created()
    {
        $manager = Manager::factory()->create([
            'name' => 'João Silva',
            'email' => 'joao@example.com',
        ]);

        $this->assertInstanceOf(Manager::class, $manager);
        $this->assertEquals('João Silva', $manager->name);
        $this->assertEquals('joao@example.com', $manager->email);
        $this->assertDatabaseHas('managers', [
            'email' => 'joao@example.com',
        ]);
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $manager = new Manager();

        $fillable = $manager->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
    }

    /** @test */
    public function it_hides_password_and_remember_token()
    {
        $manager = Manager::factory()->create([
            'remember_token' => 'token123',
        ]);

        $array = $manager->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    /** @test */
    public function it_requires_unique_email()
    {
        $email = 'teste@example.com';

        Manager::factory()->create(['email' => $email]);

        $this->expectException(QueryException::class);

        Manager::factory()->create(['email' => $email]);
    }

    /** @test */
    public function it_has_employees_relationship()
    {
        $manager = Manager::factory()->create();

        $this->assertInstanceOf(
            HasMany::class,
            $manager->employees()
        );
    }

    /** @test */
    public function it_can_have_multiple_employees()
    {
        $manager = Manager::factory()->create();

        $employee1 = Employee::factory()->forManager($manager)->create();
        $employee2 = Employee::factory()->forManager($manager)->create();

        $this->assertEquals(2, $manager->employees()->count());
        $this->assertTrue($manager->employees->contains($employee1));
        $this->assertTrue($manager->employees->contains($employee2));
    }

    /** @test */
    public function it_uses_has_api_tokens_trait()
    {
        $manager = new Manager();

        $traits = class_uses($manager);

        $this->assertArrayHasKey(
            'Laravel\Sanctum\HasApiTokens',
            $traits
        );
    }

    /** @test */
    public function it_can_create_sanctum_token()
    {
        $manager = Manager::factory()->create();

        $token = $manager->createToken('test-token');

        $this->assertNotNull($token);
        $this->assertIsString($token->plainTextToken);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => Manager::class,
            'tokenable_id' => $manager->id,
            'name' => 'test-token',
        ]);
    }

    /** @test */
    public function it_cascade_deletes_employees_when_deleted()
    {
        $manager = Manager::factory()->create();
        $employee = Employee::factory()->forManager($manager)->create();

        $employeeId = $employee->id;

        $manager->delete();

        $this->assertDatabaseMissing('managers', ['id' => $manager->id]);
        $this->assertDatabaseMissing('employees', ['id' => $employeeId]);
    }

    /** @test */
    public function it_hashes_password_correctly()
    {
        $password = 'mySecretPassword123';

        $manager = Manager::factory()->create([
            'password' => Hash::make($password),
        ]);

        $this->assertTrue(Hash::check($password, $manager->password));
        $this->assertFalse(Hash::check('wrongPassword', $manager->password));
    }

    /** @test */
    public function it_can_create_multiple_managers()
    {
        $managers = Manager::factory()->count(5)->create();

        $this->assertCount(5, $managers);
        $this->assertEquals(5, Manager::count());
    }

    /** @test */
    public function it_generates_unique_emails_for_multiple_managers()
    {
        $managers = Manager::factory()->count(10)->create();

        $emails = $managers->pluck('email')->toArray();
        $uniqueEmails = array_unique($emails);

        $this->assertCount(10, $uniqueEmails);
    }
}
