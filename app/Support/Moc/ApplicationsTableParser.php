<?php

namespace App\Support\Moc;

use App\Enums\StatusEnum;

/**
 * Parses the single pipe table under "## Pipeline" in `_Applications.md`. Every
 * row in that file is something that was sent, so an unmapped status falls back
 * to `applied` rather than being dropped.
 */
class ApplicationsTableParser
{
    /**
     * @return list<MocRow>
     */
    public function parse(string $markdown): array
    {
        $lines = preg_split('/\R/', $markdown) ?: [];

        $rows = [];
        $header = null;
        $inTable = false;

        foreach ($lines as $line) {
            $isPipe = str_starts_with(ltrim($line), '|');

            if (! $isPipe) {
                if ($inTable) {
                    break;
                }

                continue;
            }

            $cells = array_map('trim', explode('|', trim($line, "| \t")));

            if ($this->isSeparatorRow($cells)) {
                continue;
            }

            if ($header === null) {
                $header = array_map(fn (string $c) => mb_strtolower($c), $cells);
                $inTable = true;

                continue;
            }

            $inTable = true;
            $row = $this->combine($header, $cells);

            $company = $this->cleanCompany($row['company'] ?? '');
            $role = trim(strip_tags($row['role'] ?? ''));

            if ($company === '' || $role === '') {
                continue;
            }

            $rows[] = new MocRow(
                company: $company,
                role: $role,
                status: $this->mapStatus($row['status'] ?? ''),
                appliedAt: $this->parseDate($row['applied'] ?? ''),
                postingUrl: $this->firstUrl($row['channel'] ?? ''),
            );
        }

        return $rows;
    }

    /**
     * @param  list<string>  $header
     * @param  list<string>  $cells
     * @return array<string, string>
     */
    private function combine(array $header, array $cells): array
    {
        $width = min(count($header), count($cells));

        return array_combine(
            array_slice($header, 0, $width),
            array_slice($cells, 0, $width),
        );
    }

    /**
     * @param  list<string>  $cells
     */
    private function isSeparatorRow(array $cells): bool
    {
        return preg_match('/^:?-{2,}:?$/', str_replace(' ', '', $cells[0] ?? '')) === 1;
    }

    private function cleanCompany(string $raw): string
    {
        $value = preg_replace('/\*\*|`/', '', trim($raw)) ?? '';
        $value = preg_replace('/\s*\(.*$/', '', $value) ?? '';   // "(agency), client = …"
        $value = preg_replace('/,.*$/', '', $value) ?? '';        // "…, client = unknown"

        return trim($value);
    }

    private function parseDate(string $raw): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($raw)) === 1 ? trim($raw) : null;
    }

    private function firstUrl(string $raw): ?string
    {
        return preg_match('#https?://[^\s`)\]]+#', $raw, $m) === 1 ? rtrim($m[0], '.,') : null;
    }

    private function mapStatus(string $raw): StatusEnum
    {
        $value = trim(mb_strtolower(strip_tags($raw)), "*_ \t");

        return match (true) {
            str_contains($value, 'no response'), str_contains($value, 'ghost') => StatusEnum::Ghosted,
            str_contains($value, 'reject') => StatusEnum::Rejected,
            str_contains($value, 'withdraw') => StatusEnum::Withdrawn,
            str_contains($value, 'offer') => StatusEnum::Offer,
            str_contains($value, 'interview') => StatusEnum::Interview,
            str_contains($value, 'screen'), str_contains($value, 'acknowledg') => StatusEnum::Screening,
            default => StatusEnum::Applied,
        };
    }
}
