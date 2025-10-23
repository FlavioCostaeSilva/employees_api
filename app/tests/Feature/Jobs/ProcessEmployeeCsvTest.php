<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessEmployeeCsv;
use App\Mail\EmployeeCsvProcessedFailure;
use App\Mail\EmployeeCsvProcessedSuccess;
use App\Models\Employee;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessEmployeeCsvTest extends TestCase
{
    use RefreshDatabase;
    protected string $tempFilePath;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->testFilePath = storage_path('app/test_employees.csv');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFilePath)) {
            unlink($this->testFilePath);
        }
        if (isset($this->tempFilePath) && file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }

        parent::tearDown();
    }

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

    /** @test */
    public function it_sends_success_email_after_processing_valid_csv()
    {
        $csvContent = "name,email,cpf,city,state\n";
        $csvContent .= "João Silva,joao@example.com,37204103076,São Paulo,SP\n";
        $csvContent .= "Maria Santos,maria@example.com,07652144078,Rio de Janeiro,RJ\n";

        file_put_contents($this->testFilePath, $csvContent);

        $manager = Manager::factory()->create([
            'email' => 'manager@example.com'
        ]);

        $job = new ProcessEmployeeCsv($manager, $this->testFilePath, 'test-job-123');
        $job->handle();

        Mail::assertSent(EmployeeCsvProcessedSuccess::class, 1);
    }

    /** @test */
    public function it_sends_failure_email_when_file_does_not_exist()
    {
        $nonExistentPath = '/tmp/non_existent_file.csv';
        $jobId = 'test-job-' . uniqid();

        $manager = Manager::factory()->create([
            'email' => 'manager@example.com'
        ]);

        $job = new ProcessEmployeeCsv($manager, $nonExistentPath, $jobId);

        $job->tries = 3;
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('sendFailureEmail');

        $method->invoke($job, new \Exception("File not found: $nonExistentPath"));

        Mail::assertSent(EmployeeCsvProcessedFailure::class);
    }

    /** @test */
    public function it_sends_failure_email_when_csv_has_missing_headers()
    {
        $csvContent = "name,email,cpf\n" .
            "John Doe,john@example.com,12345678909";

        $this->tempFilePath = $this->createTempCsvFile($csvContent);
        $jobId = 'test-job-' . uniqid();

        $manager = Manager::factory()->create([
            'email' => 'manager@example.com'
        ]);

        $job = new ProcessEmployeeCsv($manager, $this->tempFilePath, $jobId);

        try {
            $job->handle();
        } catch (\Exception $e) {
        }

        $job->tries = 3;
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('sendFailureEmail');

        $method->invoke($job, new \Exception("Columns not found: city, state"));

        Mail::assertSent(EmployeeCsvProcessedFailure::class);
    }

    /** @test */
    public function it_sends_failure_email_when_csv_is_empty()
    {
        $csvContent = "name,email,cpf,city,state\n";

        $this->tempFilePath = $this->createTempCsvFile($csvContent);
        $jobId = 'test-job-' . uniqid();

        $manager = Manager::factory()->create([
            'email' => 'manager@example.com'
        ]);

        $job = new ProcessEmployeeCsv($manager, $this->tempFilePath, $jobId);

        try {
            $job->handle();
        } catch (\Exception $e) {
        }

        $job->tries = 3;
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('sendFailureEmail');

        $method->invoke($job, new \Exception("Empty CSV"));

        Mail::assertSent(EmployeeCsvProcessedFailure::class);
    }

    private function createTempCsvFile(string $content): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tempFile, $content);
        return $tempFile;
    }
}
