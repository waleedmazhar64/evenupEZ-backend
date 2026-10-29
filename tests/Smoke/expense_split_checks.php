<?php

declare(strict_types=1);

use App\Domain\Expenses\ExpenseSplitter;

require __DIR__.'/../../app/Domain/Expenses/ExpenseSplitter.php';

$splitter = new ExpenseSplitter();
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    $checks++;
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$reject = static function (callable $action, string $message) use ($assert): void {
    try {
        $action();
    } catch (InvalidArgumentException) {
        $assert(true, $message);
        return;
    }
    $assert(false, $message);
};

// Regression: the previous round(10 / 3, 2) approach totals only 9.99.
$shares = $splitter->equal('10.00', [11, 22, 33]);
$assert($shares === [
    ['user_id' => 11, 'amount' => 3.34],
    ['user_id' => 22, 'amount' => 3.33],
    ['user_id' => 33, 'amount' => 3.33],
], '10.00 / 3 must retain the final penny and participant order.');

$assert($splitter->equal('0.01', [1, 2, 3]) === [
    ['user_id' => 1, 'amount' => 0.01],
    ['user_id' => 2, 'amount' => 0.0],
    ['user_id' => 3, 'amount' => 0.0],
], 'One penny can be shared without manufacturing extra money.');
$assert($splitter->equal('99999999.99', [1])[0]['amount'] === 99999999.99, 'Database maximum must be supported.');
$assert($splitter->custom('0.30', [
    ['user_id' => 1, 'amount' => '0.10'],
    ['user_id' => 2, 'amount' => '0.20'],
])[1]['amount'] === 0.20, 'Custom totals must avoid binary floating-point equality.');
$assert($splitter->custom('1.00', [
    ['user_id' => '1', 'amount' => 0],
    ['user_id' => 2, 'amount' => 1],
])[0]['amount'] === 0.0, 'Zero is a valid custom share.');

$reject(fn () => $splitter->equal('10', []), 'Empty participants must fail.');
$reject(fn () => $splitter->equal('10', [1, '1']), 'Canonical duplicate IDs must fail.');
$reject(fn () => $splitter->equal('10', [true]), 'Boolean IDs must fail.');
$reject(fn () => $splitter->equal('10', range(1, 1001)), 'Participant limit must be enforced.');
$reject(fn () => $splitter->equal('0', [1]), 'Zero total must fail.');
foreach (['-0.01', '1.001', '1e2', '100000000', '', 'NaN', true, null, INF] as $invalid) {
    $reject(fn () => $splitter->minorUnits($invalid), 'Invalid money input must fail.');
}
$reject(fn () => $splitter->custom('1', []), 'Empty custom shares must fail.');
$reject(fn () => $splitter->custom('1', [['user_id' => 1]]), 'Missing custom amount must fail.');
$reject(fn () => $splitter->custom('1', [
    ['user_id' => 1, 'amount' => '.5'],
    ['user_id' => 1, 'amount' => '.5'],
]), 'Malformed or duplicate custom shares must fail.');
$reject(fn () => $splitter->custom('1', [['user_id' => 1, 'amount' => '0.99']]), 'Underallocation must fail.');
$reject(fn () => $splitter->custom('1', [['user_id' => 1, 'amount' => '1.01']]), 'Overallocation must fail.');

// Deterministic broad input checks: conserve value, keep order, and stay within one penny.
foreach ([1, 2, 3, 7, 99, 100, 101, 1000, 1000001, 9999999999] as $total) {
    foreach ([1, 2, 3, 7, 13, 100, 1000] as $count) {
        $decimal = intdiv($total, 100).'.'.str_pad((string) ($total % 100), 2, '0', STR_PAD_LEFT);
        $shares = $splitter->equal($decimal, range(1, $count));
        $minor = array_map(fn ($share): int => $splitter->minorUnits($share['amount']), $shares);
        $assert(array_sum($minor) === $total, 'Allocation must conserve every penny.');
        $assert(max($minor) - min($minor) <= 1, 'Equal shares may differ by at most one penny.');
        $assert(array_column($shares, 'user_id') === range(1, $count), 'Participant order must be retained.');
    }
}

fwrite(STDOUT, "PASS: {$checks} expense allocation checks\n");
