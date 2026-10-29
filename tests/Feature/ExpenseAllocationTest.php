<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ExpenseAllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_equal_split_conserves_the_total_in_the_api_and_database(): void
    {
        $users = User::factory()->count(3)->create();
        Passport::actingAs($users->first());

        $response = $this->postJson('/api/expenses', [
            'name' => 'Dinner',
            'amount' => '10.00',
            'paid_by' => $users->first()->id,
            'split_type' => 'equal',
            'split_users' => $users->modelKeys(),
            'payment_frequency' => 'onetime',
        ])->assertCreated();

        $this->assertEquals([3.34, 3.33, 3.33], array_column($response->json('expense.split_options'), 'amount'));
        $this->assertDatabaseCount('expenses', 1);
        $this->assertEquals([3.34, 3.33, 3.33], array_column(\App\Models\Expense::first()->split_options, 'amount'));
    }

    public function test_empty_participants_return_validation_errors_without_writing_an_expense(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);
        $this->postJson('/api/expenses', [
            'name' => 'Dinner', 'amount' => '10.00', 'paid_by' => $user->id,
            'split_type' => 'equal', 'split_users' => [], 'payment_frequency' => 'onetime',
        ])->assertUnprocessable()->assertJsonValidationErrors('split_users');
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_custom_total_mismatch_is_rejected_without_writing_an_expense(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);
        $this->postJson('/api/expenses', [
            'name' => 'Dinner', 'amount' => '10.00', 'paid_by' => $user->id,
            'split_type' => 'custom', 'custom_splits' => [['user_id' => $user->id, 'amount' => '9.99']],
            'payment_frequency' => 'onetime',
        ])->assertUnprocessable()->assertJsonValidationErrors('split');
        $this->assertDatabaseCount('expenses', 0);
    }
}
