<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Transaction::with('invoice.house');

        if ($request->has('type') && in_array($request->type, ['income', 'expenses'])) {
            $query->where('transaction_type', $request->type);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('month')) {
            $query->whereMonth('transaction_date', $request->month);
        }

        if ($request->has('year')) {
            $query->whereYear('transaction_date', $request->year);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->latest()->get();

        return $this->successResponse($transactions, 'Daftar transaksi berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_type' => 'required|in:income,expenses',
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string',
            'invoice_id' => 'nullable|exists:invoices,id',
        ]);

        $transaction = Transaction::create($validated);

        return $this->successResponse($transaction, 'Transaksi berhasil ditambahkan', 201);
    }

    public function show(string $id): JsonResponse
    {
        $transaction = Transaction::with('invoice.house')->find($id);

        if (!$transaction) {
            return $this->errorResponse('Transaksi tidak ditemukan', null, 404);
        }

        return $this->successResponse($transaction, 'Detail transaksi berhasil diambil');
    }

    public function destroy(string $id): JsonResponse
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return $this->errorResponse('Transaksi tidak ditemukan', null, 404);
        }

        $transaction->delete();

        return $this->successResponse(null, 'Transaksi berhasil dihapus');
    }
}
