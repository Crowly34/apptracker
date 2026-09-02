<?php

namespace App\Support\Moc;

use App\Enums\StatusEnum;
use App\Enums\TierEnum;

/**
 * Parses the "### Company, Role (date)" verdict entries in `_Job Sort Queue.md`.
 *
 * The file is an append-only log, so most entries are already sent or killed.
 * Only entries whose verdict is still live are returned, at status `queued`;
 * the caller is expected to drop any that already exist from `_Applications.md`.
 */
class JobSortQueueParser
{
    /**
     * @return list<MocRow>
     */
    public function parse(string $markdown): array
    {
        preg_match_all('/^### (.+?)$\n(.*?)(?=^### |\z)/ms', $markdown, $matches, PREG_SET_ORDER);

        $rows = [];

        foreach ($matches as [, $heading, $body]) {
            [$company, $role] = $this->splitHeading($heading);

            if ($company === '' || $role === '') {
                continue;
            }

            $tierToken = $this->field($body, 'Tier');
            $call = mb_strtolower($this->field($body, 'Call'));

            if (! $this->isLive($tierToken, $call)) {
                continue;
            }

            $rows[] = new MocRow(
                company: $company,
                role: $role,
                status: StatusEnum::Queued,
                tier: $this->mapTier($this->field($body, 'Bucket')),
            );
        }

        return $rows;
    }

    /**
     * Split "Company, Role" on the first comma that sits outside parentheses,
     * then strip a trailing " (YYYY-MM-DD)" off the role.
     *
     * @return array{0: string, 1: string}
     */
    private function splitHeading(string $heading): array
    {
        $heading = trim($heading);
        $depth = 0;
        $cut = null;

        foreach (str_split($heading) as $i => $char) {
            $depth += match ($char) {
                '(' => 1,
                ')' => -1,
                default => 0,
            };

            if ($char === ',' && $depth <= 0) {
                $cut = $i;
                break;
            }
        }

        if ($cut === null) {
            return ['', ''];
        }

        $company = trim(substr($heading, 0, $cut));
        $role = trim(substr($heading, $cut + 1));
        $role = trim(preg_replace('/\s*\(\d{4}-\d{2}-\d{2}\)\s*$/', '', $role) ?? $role);

        return [$this->cleanCompany($company), $role];
    }

    private function cleanCompany(string $raw): string
    {
        return trim(preg_replace('/\*\*|`/', '', $raw) ?? $raw);
    }

    /**
     * Value of a "- **Key:** …" bullet, up to the first newline or " · " divider.
     */
    private function field(string $body, string $key): string
    {
        $pattern = '/\*\*'.preg_quote($key, '/').':?\*\*\s*\**\s*(.+?)(?:\n|\s+·\s+|$)/';

        return preg_match($pattern, $body, $m) === 1 ? trim($m[1], "* \t") : '';
    }

    private function isLive(string $tierToken, string $call): bool
    {
        if (str_contains($call, 'sent')) {
            return false;
        }

        if (preg_match('/^dead/i', trim($tierToken)) === 1) {
            return false;
        }

        foreach (['skip', 'do not', 'dead'] as $kill) {
            if (str_contains($call, $kill)) {
                return false;
            }
        }

        return true;
    }

    private function mapTier(string $bucket): ?TierEnum
    {
        return TierEnum::tryFrom(strtoupper(trim($bucket)));
    }
}
