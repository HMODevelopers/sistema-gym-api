<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportacionCsvService
{
    public function stream(string $filename, array $headers, callable $callback): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $callback): void {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            $this->escribirFila($handle, $headers);
            $callback($handle, $this);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function escribirFila($handle, array $row): void
    {
        fputcsv($handle, array_map(fn ($value) => $this->normalizarValor($value), $row));
    }

    public function normalizarValor(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return trim((string) $value);
    }
}
