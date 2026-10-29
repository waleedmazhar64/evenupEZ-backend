# Keeping every penny in an expense split

## Problem

EvenUpEZ's original expense controller calculated an equal share with `round(total / participants, 2)` and repeated it for every participant. For a 10.00 expense shared by three people, it stored 3.33 + 3.33 + 3.33: one penny was missing. Empty participant lists could also reach division, and custom totals were compared using floating-point equality.

## Change

The `ExpenseSplitter` separates money allocation from the HTTP controller. It parses amounts into integer minor units, divides using integer arithmetic, and distributes the remainder in participant order. The same 10.00 becomes 3.34 + 3.33 + 3.33.

Custom splits are summed in minor units. The service rejects duplicate participants, missing/empty splits, negative totals, more than two decimal places, and amounts beyond the existing DECIMAL(10,2) column. The HTTP controller validates required fields before calculating and returns a 422 validation response for invalid split inputs.

Numeric `amount` values are retained in the existing response shape; calculations and comparisons happen in integers. Participant order provides a deterministic tie-breaker. This is easy to explain and test, but repeatedly favours the earliest participant for remainder pennies. A rotating remainder policy would be a separate product decision.

## How to inspect it

1. Read `app/Domain/Expenses/ExpenseSplitter.php` for the domain rules.
2. Read `app/Http/Controllers/ExpenseController.php` for the integration.
3. Run `php tests/Smoke/expense_split_checks.php` for isolated regression and invariant checks.
4. Run `php artisan test --filter=ExpenseAllocationTest` on a disposable test database for authenticated HTTP and persistence checks. The workflow uses in-memory SQLite.
5. Inspect the `Expense allocation checks` Actions run for the exact commit being reviewed. A workflow file alone is not a passing result.

## Verified result

The implementation was merged in [PR #2](https://github.com/waleedmazhar64/evenupEZ-backend/pull/2). [The reviewed head passed CI](https://github.com/waleedmazhar64/evenupEZ-backend/actions/runs/40808061174) on PHP 8.2 and PHP 8.3: 234 isolated money checks and three authenticated API/database tests (12 assertions) per version. This verifies the focused allocation change, rather than the entire application.

## What this demonstrates

Reproducing a business defect, preserving an invariant, extracting a small domain boundary, keeping API compatibility, choosing and explaining a trade-off, and testing the controller/database path as well as the calculation.

## Scope and provenance

This is a portfolio improvement prepared with AI assistance in October 2026. It does not establish historic commercial test coverage, payment-processing compliance, production deployment or whole-application security. Group/expense authorisation and file-access controls require a separate review before a public demo with real user data. Use synthetic data for a demonstration.

The author should walk through the implementation and tests personally before presenting it in an interview. The commercial Seller Legend achievements belong to earlier employment and are separate from this personal project.
