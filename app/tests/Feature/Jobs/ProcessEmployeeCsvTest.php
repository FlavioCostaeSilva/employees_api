<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessEmployeeCsv;
use App\Models\Employee;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessEmployeeCsvTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_validates_csv_file_is_required()
    {
        $manager = Manager::factory()->create();

        $response = $this->actingAs($manager, 'sanctum')
            ->postJson('/api/employees/upload-csv', []);

        $response->assertStatus(400);
    }

    /** @test */
    public function it_validates_csv_file_type()
    {
        $manager = Manager::factory()->create();

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($manager, 'sanctum')
            ->post('/api/employees/upload-csv', [
                'file_csv' => $file
            ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function it_validates_csv_has_required_columns()
    {
        $manager = Manager::factory()->create();

        // CSV sem coluna 'email'
        $csv = "name,cpf,city,state\n";
        $csv .= "João Silva,12345678909,São Paulo,SP\n";

        $file = UploadedFile::fake()->createWithContent('invalid.csv', $csv);

        $response = $this->actingAs($manager, 'sanctum')
            ->postJson('/api/employees/upload-csv', [
                'file_csv' => $file
            ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function it_dispatches_job_on_valid_csv_upload()
    {
        Queue::fake();

        $manager = Manager::factory()->create();

        $csv = "name,email,cpf,city,state\n";
        $csv .= "João Silva,joao@test.com,12345678909,São Paulo,SP\n";

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csv);

        $response = $this->actingAs($manager, 'sanctum')
            ->post('/api/employees/upload-csv', [
                'csv_file' => $file
            ]);

        $response->assertStatus(200);

        Queue::assertPushed(ProcessEmployeeCsv::class, function ($job) use ($manager) {
            return $job->manager->id === $manager->id;
        });
    }

    /** @test */
    public function it_processes_valid_csv_with_league_csv()
    {
        $manager = Manager::factory()->create();

        // Criar arquivo CSV
        $csvContent = "name,email,cpf,city,state\n";
        $csvContent .= "João Silva,joao@test.com,12345678909,São Paulo,SP\n";
        $csvContent .= "Maria Santos,maria@test.com,19976745052,Rio de Janeiro,RJ\n";

        $filePath = storage_path('app/csv_imports/test.csv');

        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        file_put_contents($filePath, $csvContent);

        $job = new ProcessEmployeeCsv($manager, $filePath, 'test-job-id');
        $job->handle();

        $this->assertEquals(2, Employee::count());

        $this->assertDatabaseHas('employees', [
            'name' => 'João Silva',
            'email' => 'joao@test.com',
            'cpf' => '12345678909',
            'manager_id' => $manager->id
        ]);

        $this->assertDatabaseHas('employees', [
            'name' => 'Maria Santos',
            'email' => 'maria@test.com',
            'manager_id' => $manager->id
        ]);
    }

    /** @test */
    public function it_converts_state_names_to_abbreviations()
    {
        $manager = Manager::factory()->create();

        $csvContent = "name,email,cpf,city,state\n";
        $csvContent .= "João Silva,joao@test.com,12345678909,Recife,Pernambuco\n";
        $csvContent .= "Maria Santos,maria@test.com,19976745052,Porto Alegre,Rio Grande do Sul\n";

        $filePath = storage_path('app/csv_imports/test_states.csv');
        file_put_contents($filePath, $csvContent);

        $job = new ProcessEmployeeCsv($manager, $filePath, 'test-job-states');
        $job->handle();

        $this->assertDatabaseHas('employees', [
            'name' => 'João Silva',
            'state' => 'PE'
        ]);

        $this->assertDatabaseHas('employees', [
            'name' => 'Maria Santos',
            'state' => 'RS'
        ]);
    }

    /** @test */
    public function it_handles_cpf_with_mask()
    {
        $manager = Manager::factory()->create();

        $csvContent = "name,email,cpf,city,state\n";
        $csvContent .= "João Silva,joao@test.com,123.456.789-09,São Paulo,SP\n";

        $filePath = storage_path('app/csv_imports/test_cpf.csv');
        file_put_contents($filePath, $csvContent);

        $job = new ProcessEmployeeCsv($manager, $filePath, 'test-job-cpf');
        $job->handle();

        // CPF deve ser salvo sem máscara
        $this->assertDatabaseHas('employees', [
            'email' => 'joao@test.com',
            'cpf' => '12345678909'
        ]);
    }
}
