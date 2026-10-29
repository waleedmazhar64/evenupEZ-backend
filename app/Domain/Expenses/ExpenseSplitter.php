<?php

declare(strict_types=1);

namespace App\Domain\Expenses;

use InvalidArgumentException;

/** Exact two-decimal expense allocation; no framework or database dependency. */
final class ExpenseSplitter
{
    private const MAX_MINOR_UNITS = 9_999_999_999; // DECIMAL(10, 2)

    /** @return list<array{user_id: int, amount: float}> */
    public function equal(mixed $amount, array $userIds): array
    {
        $total = $this->positiveTotal($amount);
        $ids = $this->participantIds($userIds);
        $base = intdiv($total, count($ids));
        $remainder = $total % count($ids);

        // Input order is the tie-breaker; each extra share is one penny.
        return array_map(
            fn (int $id, int $index): array => [
                'user_id' => $id,
                'amount' => $this->apiAmount($base + ($index < $remainder ? 1 : 0)),
            ],
            $ids,
            array_keys($ids),
        );
    }

    /** @return list<array{user_id: int, amount: float}> */
    public function custom(mixed $amount, array $splits): array
    {
        $total = $this->positiveTotal($amount);
        $ids = [];
        $minorAmounts = [];

        foreach ($splits as $split) {
            if (!is_array($split) || !array_key_exists('user_id', $split) || !array_key_exists('amount', $split)) {
                throw new InvalidArgumentException('Every custom split needs a user_id and amount.');
            }
            $ids[] = $split['user_id'];
            $minorAmounts[] = $this->minorUnits($split['amount']);
        }

        $ids = $this->participantIds($ids);
        if (array_sum($minorAmounts) !== $total) {
            throw new InvalidArgumentException('Custom splits must add up to the expense total.');
        }

        return array_map(
            fn (int $id, int $minor): array => ['user_id' => $id, 'amount' => $this->apiAmount($minor)],
            $ids,
            $minorAmounts,
        );
    }

    public function minorUnits(mixed $amount): int
    {
        if (!is_string($amount) && !is_int($amount) && !is_float($amount)) {
            throw new InvalidArgumentException('Amounts must be decimal numbers with at most two decimal places.');
        }

        // Convert numeric JSON input to text at the boundary; calculate only in integers.
        $decimal = (string) $amount;
        if (!preg_match('/\A([0-9]{1,8})(?:\.([0-9]{1,2}))?\z/', $decimal, $parts)) {
            throw new InvalidArgumentException('Amounts must be between 0 and 99999999.99 with at most two decimal places.');
        }

        $minor = ((int) $parts[1] * 100) + (int) str_pad($parts[2] ?? '', 2, '0');
        if ($minor > self::MAX_MINOR_UNITS) {
            throw new InvalidArgumentException('Amount exceeds the supported database precision.');
        }

        return $minor;
    }

    private function positiveTotal(mixed $amount): int
    {
        $total = $this->minorUnits($amount);
        if ($total < 1) {
            throw new InvalidArgumentException('Expense total must be at least 0.01.');
        }

        return $total;
    }

    /** @return list<int> */
    private function participantIds(array $ids): array
    {
        if (count($ids) < 1 || count($ids) > 1000) {
            throw new InvalidArgumentException('Choose between 1 and 1000 participants.');
        }

        $normalised = [];
        foreach ($ids as $id) {
            if ((!is_int($id) && !is_string($id)) || !preg_match('/\A[1-9][0-9]*\z/', (string) $id)
                || filter_var($id, FILTER_VALIDATE_INT) === false) {
                throw new InvalidArgumentException('Participant IDs must be positive integers.');
            }
            $normalised[] = (int) $id;
        }

        if (count(array_unique($normalised)) !== count($normalised)) {
            throw new InvalidArgumentException('A participant may appear only once.');
        }

        return $normalised;
    }

    private function apiAmount(int $minor): float
    {
        // Retain the existing numeric JSON shape; allocation and validation use pence.
        return (float) (intdiv($minor, 100).'.'.str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT));
    }
}
