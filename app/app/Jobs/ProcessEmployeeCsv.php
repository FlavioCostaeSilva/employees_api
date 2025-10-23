<?php

namespace App\Jobs;

use App\Helpers\BrazilianStates;
use App\Http\Requests\StoreEmployeeRequest;
use App\Mail\EmployeeCsvProcessedSuccess;
use App\Models\Employee;
use App\Models\Manager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;
use League\Csv\Statement;

class ProcessEmployeeCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Manager $manager;
    public string $filePath;
    public string $jobId;
    public int $timeout = 600;
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(Manager $manager, string $filePath, string $jobId)
    {
        $this->manager = $manager;
        $this->filePath = $filePath;
        $this->jobId = $jobId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info("Starting process", [
            'manager_id' => $this->manager->id,
            'job_id' => $this->jobId,
            'file' => $this->filePath
        ]);

        $processed = 0;
        $errors = 0;
        $errorDetails = [];

        try {
            if (!file_exists($this->filePath)) {
                throw new \Exception("Arquivo não encontrado: {$this->filePath}");
            }

            $csv = Reader::createFromPath($this->filePath, 'r');
            $csv->setHeaderOffset(0);

            if (!mb_check_encoding(file_get_contents($this->filePath), 'UTF-8')) {
                $csv->addStreamFilter('convert.iconv.ISO-8859-1/UTF-8');
            }

            $header = $csv->getHeader();

            $expectedHeaders = ['name', 'email', 'cpf', 'city', 'state'];
            $missingHeaders = array_diff($expectedHeaders, $header);

            if (!empty($missingHeaders)) {
                throw new \Exception("Columns not found: " . implode(', ', $missingHeaders));
            }

            $totalRecords = count($csv);

            if ($totalRecords === 0) {
                throw new \Exception("Empty CSV");
            }

            $records = Statement::create()->process($csv);
            $lineNumber = 1;

            foreach ($records as $record) {
                $lineNumber++;

                try {
                    $employeeData = [
                        'name' => trim($record['name'] ?? ''),
                        'email' => trim($record['email'] ?? ''),
                        'cpf' => trim($record['cpf'] ?? ''),
                        'city' => trim($record['city'] ?? ''),
                        'state' => trim($record['state'] ?? ''),
                    ];

                    $employeeData['cpf'] = preg_replace('/\D/', '', $employeeData['cpf']);

                    $stateAbbreviation = BrazilianStates::toAbbreviation($employeeData['state']);
                    if ($stateAbbreviation === null) {
                        $employeeData['state'] = strtoupper(substr($employeeData['state'], 0, 2));
                    } else {
                        $employeeData['state'] = $stateAbbreviation;
                    }

                    $validator = $this->validateEmployeeData($employeeData);

                    if ($validator->fails()) {
                        $errorDetails[] = [
                            'line' => $lineNumber,
                            'data' => $record,
                            'errors' => $validator->errors()->toArray()
                        ];
                        $errors++;
                        continue;
                    }

                    $employeeData['manager_id'] = $this->manager->id;

                    Employee::create($employeeData);
                    $processed++;
                } catch (\Exception $e) {
                    $errorDetails[] = [
                        'line' => $lineNumber,
                        'data' => $record,
                        'error' => $e->getMessage()
                    ];
                    $errors++;

                    Log::warning("Error on line {$lineNumber}", [
                        'error' => $e->getMessage(),
                        'data' => $record
                    ]);
                }
            }

            $result = [
                'total_lines' => $totalRecords,
                'processed' => $processed,
                'errors' => $errors,
                'error_details' => array_slice($errorDetails, 0, 100),
                'finished_at' => now()->toDateTimeString(),
            ];

            if (file_exists($this->filePath)) {
                unlink($this->filePath);
            }

            Log::info("CSV processed with success!", [
                'manager_id' => $this->manager->id,
                'job_id' => $this->jobId,
                'result' => $result
            ]);

            $this->sendSuccessEmail($result);
        } catch (\Exception $e) {
            Log::error("Error processing CSV file", [
                'manager_id' => $this->manager->id,
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    private function validateEmployeeData(array $data): \Illuminate\Validation\Validator
    {
        $request = new StoreEmployeeRequest();

        $rules = $request->rules();
        $messages = $request->messages();

        return Validator::make($data, $rules, $messages);
    }

    private function sendSuccessEmail(array $result): void
    {
        try {
            Mail::to($this->manager->email)
                ->send(new EmployeeCsvProcessedSuccess($this->manager, $result));

            Log::info("Success email sent", [
                'manager_id' => $this->manager->id,
                'manager_email' => $this->manager->email
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send success email", [
                'manager_id' => $this->manager->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
