<?php

declare(strict_types=1);

namespace App\DTO;

class ImportResult
{
    private int $totalRows = 0;
    private int $imported = 0;
    private int $updated = 0;
    private int $skipped = 0;

    /** @var array<int, array{row: int, error: string}> */
    private array $errors = [];

    public function getTotalRows(): int
    {
        return $this->totalRows;
    }

    public function setTotalRows(int $totalRows): void
    {
        $this->totalRows = $totalRows;
    }

    public function getImported(): int
    {
        return $this->imported;
    }

    public function addImported(): void
    {
        $this->imported++;
    }

    public function getUpdated(): int
    {
        return $this->updated;
    }

    public function addUpdated(): void
    {
        $this->updated++;
    }

    public function getSkipped(): int
    {
        return $this->skipped;
    }

    public function addSkipped(): void
    {
        $this->skipped++;
    }

    public function addError(int $row, string $error): void
    {
        $this->errors[] = ['row' => $row, 'error' => $error];
    }

    /** @return array<int, array{row: int, error: string}> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    public function toArray(): array
    {
        return [
            'total_rows' => $this->totalRows,
            'imported' => $this->imported,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
            'errors_count' => count($this->errors),
        ];
    }
}
