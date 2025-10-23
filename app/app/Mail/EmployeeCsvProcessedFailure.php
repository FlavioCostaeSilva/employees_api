<?php

namespace App\Mail;

use App\Models\Manager;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmployeeCsvProcessedFailure extends Mailable
{
    use Queueable, SerializesModels;

    public Manager $manager;
    public string $errorMessage;
    public ?string $errorTrace;
    public string $failedAt;

    public function __construct(Manager $manager, string $errorMessage, ?string $errorTrace = null)
    {
        $this->manager = $manager;
        $this->errorMessage = $errorMessage;
        $this->errorTrace = $errorTrace;
        $this->failedAt = now()->toDateTimeString();
    }

    public function build(): EmployeeCsvProcessedFailure
    {
        return $this->subject('Employee Import Error')
            ->html($this->getHtmlContent());
    }

    private function getHtmlContent(): string
    {
        $managerName = htmlspecialchars($this->manager->name, ENT_QUOTES, 'UTF-8');
        $errorMessage = htmlspecialchars($this->errorMessage, ENT_QUOTES, 'UTF-8');
        $failedAt = $this->failedAt;

        $traceSection = '';
        if ($this->errorTrace) {
            $errorTrace = htmlspecialchars($this->errorTrace, ENT_QUOTES, 'UTF-8');
            $traceSection = "
                <tr>
                    <td colspan=\"2\">
                        <strong>Stack Trace:</strong>
                        <pre>$errorTrace</pre>
                    </td>
                </tr>
            ";
        }

        return "
<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Import Error</title>
</head>
<body>
    <h2>❌ Employee Import Error</h2>

    <p>Hello, <strong>$managerName</strong>!</p>

    <p>Unfortunately, an error occurred while processing the employee CSV file.</p>

    <table border=\"1\" cellpadding=\"10\" cellspacing=\"0\" width=\"100%\">
        <thead>
            <tr bgcolor=\"#f0f0f0\">
                <th colspan=\"2\">Error Details</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td width=\"30%\"><strong>Failed at:</strong></td>
                <td>$failedAt</td>
            </tr>
            <tr>
                <td><strong>Error Message:</strong></td>
                <td>$errorMessage</td>
            </tr>
            $traceSection
        </tbody>
    </table>
</body>
</html>
        ";
    }
}
