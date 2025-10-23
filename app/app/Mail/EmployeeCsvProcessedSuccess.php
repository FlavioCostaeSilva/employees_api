<?php

namespace App\Mail;

use App\Models\Manager;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmployeeCsvProcessedSuccess extends Mailable
{
    use Queueable, SerializesModels;

    public Manager $manager;
    public int $totalLines;
    public int $processed;
    public int $errors;
    public string $finishedAt;
    public array $errorDetails;

    public function __construct(Manager $manager, array $result)
    {
        $this->manager = $manager;
        $this->totalLines = $result['total_lines'];
        $this->processed = $result['processed'];
        $this->errors = $result['errors'];
        $this->finishedAt = $result['finished_at'];
        $this->errorDetails = $result['error_details'] ?? [];
    }

    public function build(): EmployeeCsvProcessedSuccess
    {
        return $this->subject("Employee import finished! $this->processed OK | $this->errors Errors")
            ->html($this->getHtmlContent());
    }

    private function getHtmlContent(): string
    {
        $managerName = htmlspecialchars($this->manager->name, ENT_QUOTES, 'UTF-8');
        $totalLines = $this->totalLines;
        $processed = $this->processed;
        $errors = $this->errors;
        $finishedAt = $this->finishedAt;

        $html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <title>Employee import finished! $this->processed OK | $this->errors Errors</title>
</head>
<body>
    <h2>Employee import finished! $this->processed OK | $this->errors Errors</h2>

    <p>Hello, <strong>$managerName</strong>!</p>

    <p>The CSV employee import file has been successfully completed.</p>

    <h3>Processing Results:</h3>

    <table border=\"1\" cellpadding=\"10\" cellspacing=\"0\">
        <tr>
            <th>Description</th>
            <th>Quantity</th>
        </tr>
        <tr>
            <td>Total Lines</td>
            <td>$totalLines</td>
        </tr>
        <tr>
            <td>Successfully Processed Records</td>
            <td>$processed</td>
        </tr>
        <tr>
            <td>Records with Errors</td>
            <td>$errors</td>
        </tr>
        <tr>
            <td>Completed At</td>
            <td>$finishedAt</td>
        </tr>
    </table>";

        if ($errors > 0) {
            $html .= $this->buildErrorSection();
        } else {
            $html .= "<p><strong>Congratulations!</strong> All records were successfully imported!</p>";
        }

        $html .= "</body></html>";

        return $html;
    }

    private function buildErrorSection(): string
    {
        $html = "<p><strong>Attention:</strong> Some records had errors during import and were not processed.</p>";

        if (!empty($this->errorDetails)) {
            $html .= "<h3>Error Details:</h3><ul>";

            foreach ($this->errorDetails as $errorDetail) {
                $html .= $this->buildErrorItem($errorDetail);
            }

            $html .= "</ul>";
        }

        return $html;
    }

    private function buildErrorItem(array $errorDetail): string
    {
        $line = $errorDetail['line'] ?? 'N/A';

        $html = "<li><strong>Line $line:</strong><br>";

        $dataInfo = $this->formatErrorData($errorDetail);
        if (!empty($dataInfo)) {
            $html .= "<em>Data:</em> $dataInfo<br>\n";
        }

        $errorMessages = $this->formatErrorMessages($errorDetail);
        if (!empty($errorMessages)) {
            $html .= "<em>Errors:</em>\n";
            $html .= "<ul>\n";
            foreach ($errorMessages as $errorMsg) {
                $html .= "<li>$errorMsg</li>\n";
            }
            $html .= "</ul>\n";
        }

        $html .= "</li>\n";

        return $html;
    }


    private function formatErrorData(array $errorDetail): string
    {
        if (!isset($errorDetail['data']) || !is_array($errorDetail['data'])) {
            return '';
        }

        $dataParts = [];
        foreach ($errorDetail['data'] as $key => $value) {
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $dataParts[] = "$key: $value";
        }

        return implode(', ', $dataParts);
    }

    private function formatErrorMessages(array $errorDetail): array
    {
        $errorMessages = [];

        if (isset($errorDetail['errors']) && is_array($errorDetail['errors'])) {
            $errorMessages = $this->extractValidationErrors($errorDetail['errors']);
        } elseif (isset($errorDetail['error'])) {
            $error = htmlspecialchars($errorDetail['error'], ENT_QUOTES, 'UTF-8');
            $errorMessages[] = $error;
        }

        return $errorMessages;
    }

    private function extractValidationErrors(array $errors): array
    {
        $errorMessages = [];

        foreach ($errors as $field => $messages) {
            if (!is_array($messages)) {
                continue;
            }

            foreach ($messages as $message) {
                $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
                $errorMessages[] = "<strong>$field:</strong> $message";
            }
        }

        return $errorMessages;
    }
}
