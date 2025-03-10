<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\User;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

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
            'custom_splits.*.status' => 'required_with:custom_splits|in:pending,partially_paid,paid',
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
                    'status' => $request->input('status', 'pending'),
                ];
            }
        } elseif ($request->split_type === 'custom') {
            $totalCustomAmount = collect($request->custom_splits)->sum('amount');
            if ($totalCustomAmount != $request->amount) {
                return response()->json(['message' => 'Custom split amounts do not equal total amount.'], 400);
            }
            foreach ($request->custom_splits as $split) {
                $splitOptions[] = [
                    'user_id' => $split['user_id'],
                    'amount' => $split['amount'],
                    'status' => $split['status'] ?? 'pending', // Status from request
                ];
            }
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

    public function updateUserStatus(Request $request, $expenseId){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:pending,partially_paid,paid',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $expense = Expense::find($expenseId);
    
        if (!$expense) {
            return response()->json(['message' => 'Expense not found.'], 404);
        }
    
        $splitOptions = $expense->split_options;
        
        // Find the user in the split_options array
        foreach ($splitOptions as &$split) {
            if ($split['user_id'] == $request->user_id) {
                $split['status'] = $request->status;
            }
        }
    
        // Save the updated expense
        $expense->split_options = $splitOptions;
        $expense->save();
    
        return response()->json([
            'message' => 'User status updated successfully.',
            'expense' => $expense,
        ]);
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
        $expense = Expense::with('payer', 'group', 'receipts', 'receipts.user')->find($id);

        if (!$expense) {
            return response()->json(['message' => 'Expense not found.'], 404);
        }

        // Get the user IDs from split_options
        $splitUserIds = collect($expense->split_options)->pluck('user_id');

        // Fetch the users involved in the splits
        $splitUsers = User::whereIn('id', $splitUserIds)->select('id', 'name', 'email')->get();

        return response()->json([
            'expense' => $expense,
            'split_users' => $splitUsers,
        ]);
    }

    public function getGroupExpenses($groupId)
    {
        // Fetch all expenses for the specified group with their payer and group details
        $expenses = Expense::with(['payer', 'group', 'receipts', 'receipts.user'])
            ->where('group_id', $groupId)
            ->get();

        if ($expenses->isEmpty()) {
            return response()->json(['message' => 'No expenses found for this group.'], 404);
        }

        // Process split options to include split users for each expense
        $expensesWithSplitUsers = $expenses->map(function ($expense) {
            $splitUserIds = collect($expense->split_options)->pluck('user_id');
            $splitUsers = User::whereIn('id', $splitUserIds)->select('id', 'name', 'email')->get();

            return [
                'expense' => $expense,
                'split_users' => $splitUsers,
            ];
        });

        return response()->json([
            'expenses' => $expensesWithSplitUsers,
        ]);
    }

    public function uploadReceipts(Request $request, $expenseId)
    {
        
        $expense = Expense::find($expenseId);

        if (!$expense) {
            return response()->json(['message' => 'Expense not found.', 'receipts_request' => $request->all(),], 404);
        }

        // Custom validation with detailed error messages
    $validator = Validator::make($request->all(), [
        'receipts' => 'required|array|min:1',
        'receipts.*.file' => 'required|file|mimes:jpeg,png,pdf',
        'receipts.*.description' => 'nullable|string|max:255',
    ]);

    // If validation fails, return a structured error response
    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
            'receipts_request' => $request->all(),
        ], 422);
    }


        $uploadedFiles = [];
        
        if ($request->has('receipts')) {
            foreach ($request->receipts as $receiptData) {
                if (!isset($receiptData['file'])) continue;

                $file = $receiptData['file'];
                $path = $file->store('receipts', 'public');

                $receipt = Receipt::create([
                    'expense_id' => $expenseId,
                    'user_id' => Auth::id(), // Link to the logged-in user
                    'file_path' => $path,
                    'description' => $receiptData['description'] ?? null,
                ]);

                $uploadedFiles[] = $receipt;
            }
        }

        return response()->json([
            'message' => 'Receipts uploaded successfully.',
            'receipts' => $uploadedFiles,
            'receipts_request' => $request->all(),
        ], 201);
    }
}
