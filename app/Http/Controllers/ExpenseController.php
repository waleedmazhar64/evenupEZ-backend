<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    // Create a new expense
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'paid_by' => 'required|exists:users,id',
            'split_type' => 'required|in:equal,custom',
            'split_users' => 'required|array',
            'split_users.*' => 'required|exists:users,id',
            'custom_splits' => 'nullable|array',
            'custom_splits.*.user_id' => 'required_with:custom_splits|exists:users,id',
            'custom_splits.*.amount' => 'required_with:custom_splits|numeric|min:0',
            'group_id' => 'nullable|exists:groups,id',
            'due_date' => 'nullable|date',
            'payment_frequency' => 'required|in:onetime,monthly,yearly',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Calculate split amounts
        $splitOptions = [];
        if ($request->split_type === 'equal') {
            $splitAmount = round($request->amount / count($request->split_users), 2);
            foreach ($request->split_users as $userId) {
                $splitOptions[] = [
                    'user_id' => $userId,
                    'amount' => $splitAmount,
                ];
            }
        } elseif ($request->split_type === 'custom') {
            $totalCustomAmount = collect($request->custom_splits)->sum('amount');
            if ($totalCustomAmount != $request->amount) {
                return response()->json(['message' => 'Custom split amounts do not equal total amount.'], 400);
            }
            $splitOptions = $request->custom_splits;
        }

        // Save the expense
        $expense = Expense::create([
            'name' => $request->name,
            'amount' => $request->amount,
            'paid_by' => $request->paid_by,
            'split_type' => $request->split_type,
            'split_options' => $splitOptions,
            'group_id' => $request->group_id,
            'due_date' => $request->due_date,
            'payment_frequency' => $request->payment_frequency,
        ]);

        return response()->json(['message' => 'Expense created successfully.', 'expense' => $expense], 201);
    }

    // Get all expenses
    public function index()
    {
        $expenses = Expense::with('payer')->get();
        return response()->json(['expenses' => $expenses]);
    }

    // View a single expense
    public function show($id)
    {
        $expense = Expense::with('payer')->find($id);

        if (!$expense) {
            return response()->json(['message' => 'Expense not found.'], 404);
        }

        return response()->json(['expense' => $expense]);
    }
}
