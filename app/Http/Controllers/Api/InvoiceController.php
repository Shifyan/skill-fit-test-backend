<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with(['house', 'resident']);

        if ($request->has('month')) {
            $query->where('month', $request->month);
        }

        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        if ($request->has('house_id')) {
            $query->where('house_id', $request->house_id);
        }

        if ($request->has('resident_id')) {
            $query->where('resident_id', $request->resident_id);
        }

        if ($request->has('status')) {
            if ($request->status === 'paid') {
                $query->where('cleaning_bill_status', 'paid')->where('security_bill_status', 'paid');
            } elseif ($request->status === 'unpaid') {
                $query->where(function ($q) {
                    $q->where('cleaning_bill_status', 'unpaid')->orWhere('security_bill_status', 'unpaid');
                });
            }
        }

        $invoices = $query->orderBy('year', 'desc')->orderBy('month', 'desc')->get();

        return $this->successResponse($invoices, 'Daftar tagihan iuran berhasil diambil');
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $month = (int) $validated['month'];
        $year = (int) $validated['year'];

        $occupiedHouses = House::where('house_status', 'occupied')
            ->with(['currentHistory.resident'])
            ->get();

        $createdCount = 0;

        DB::transaction(function () use ($occupiedHouses, $month, $year, &$createdCount) {
            foreach ($occupiedHouses as $house) {
                $resident = $house->currentHistory?->resident;
                if (!$resident) {
                    continue;
                }

                $existing = Invoice::where('house_id', $house->id)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->first();

                if (!$existing) {
                    Invoice::create([
                        'house_id' => $house->id,
                        'resident_id' => $resident->id,
                        'month' => $month,
                        'year' => $year,
                        'cleaning_bill' => 15000,
                        'security_bill' => 100000,
                        'cleaning_bill_status' => 'unpaid',
                        'security_bill_status' => 'unpaid',
                    ]);
                    $createdCount++;
                }
            }
        });

        return $this->successResponse(
            ['created_count' => $createdCount, 'month' => $month, 'year' => $year],
            "Tagihan bulan {$month}/{$year} berhasil digenerate ({$createdCount} tagihan baru dibuat)"
        );
    }

    public function show(string $id): JsonResponse
    {
        $invoice = Invoice::with(['house', 'resident', 'transactions'])->find($id);

        if (!$invoice) {
            return $this->errorResponse('Tagihan tidak ditemukan', null, 404);
        }

        return $this->successResponse($invoice, 'Detail tagihan berhasil diambil');
    }

    /**
     * Process multi-month or single invoice payments
     */
    public function pay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payments' => 'nullable|array',
            'payments.*.invoice_id' => 'required_with:payments|exists:invoices,id',
            'payments.*.type' => 'required_with:payments|in:cleaning,security,both',

            'house_id' => 'nullable|exists:houses,id',
            'year' => 'nullable|integer',
            'months' => 'nullable|array',
            'months.*' => 'integer|min:1|max:12',
            'type' => 'nullable|in:cleaning,security,both',
            'payment_date' => 'nullable|date',
        ]);

        $paymentDate = $validated['payment_date'] ?? Carbon::now()->format('Y-m-d');
        $processedCount = 0;
        $createdTransactions = [];

        DB::transaction(function () use ($validated, $paymentDate, &$processedCount, &$createdTransactions) {
            if (!empty($validated['payments'])) {
                foreach ($validated['payments'] as $item) {
                    $invoice = Invoice::with('house')->find($item['invoice_id']);
                    if (!$invoice) continue;

                    $type = $item['type'];
                    $houseNo = $invoice->house?->house_number ?? '';

                    if (($type === 'cleaning' || $type === 'both') && $invoice->cleaning_bill_status === 'unpaid') {
                        $invoice->cleaning_bill_status = 'paid';
                        $createdTransactions[] = Transaction::create([
                            'transaction_type' => 'income',
                            'category' => 'iuran_kebersihan',
                            'amount' => $invoice->cleaning_bill,
                            'transaction_date' => $paymentDate,
                            'description' => "Iuran Kebersihan Bulan {$invoice->month}/{$invoice->year} - Rumah {$houseNo}",
                            'invoice_id' => $invoice->id,
                        ]);
                    }

                    if (($type === 'security' || $type === 'both') && $invoice->security_bill_status === 'unpaid') {
                        $invoice->security_bill_status = 'paid';
                        $createdTransactions[] = Transaction::create([
                            'transaction_type' => 'income',
                            'category' => 'iuran_satpam',
                            'amount' => $invoice->security_bill,
                            'transaction_date' => $paymentDate,
                            'description' => "Iuran Satpam Bulan {$invoice->month}/{$invoice->year} - Rumah {$houseNo}",
                            'invoice_id' => $invoice->id,
                        ]);
                    }

                    $invoice->save();
                    $processedCount++;
                }
            } 
            elseif (!empty($validated['house_id']) && !empty($validated['year']) && !empty($validated['months'])) {
                $house = House::with('currentHistory.resident')->find($validated['house_id']);
                $type = $validated['type'] ?? 'both';
                $year = $validated['year'];

                foreach ($validated['months'] as $month) {
                    $invoice = Invoice::where('house_id', $house->id)
                        ->where('month', $month)
                        ->where('year', $year)
                        ->first();

                    if (!$invoice) {
                        $residentId = $house->currentHistory?->resident_id;
                        if (!$residentId) continue;

                        $invoice = Invoice::create([
                            'house_id' => $house->id,
                            'resident_id' => $residentId,
                            'month' => $month,
                            'year' => $year,
                            'cleaning_bill' => 15000,
                            'security_bill' => 100000,
                            'cleaning_bill_status' => 'unpaid',
                            'security_bill_status' => 'unpaid',
                        ]);
                    }

                    $houseNo = $house->house_number;

                    if (($type === 'cleaning' || $type === 'both') && $invoice->cleaning_bill_status === 'unpaid') {
                        $invoice->cleaning_bill_status = 'paid';
                        $createdTransactions[] = Transaction::create([
                            'transaction_type' => 'income',
                            'category' => 'iuran_kebersihan',
                            'amount' => $invoice->cleaning_bill,
                            'transaction_date' => $paymentDate,
                            'description' => "Iuran Kebersihan Bulan {$invoice->month}/{$invoice->year} - Rumah {$houseNo}",
                            'invoice_id' => $invoice->id,
                        ]);
                    }

                    if (($type === 'security' || $type === 'both') && $invoice->security_bill_status === 'unpaid') {
                        $invoice->security_bill_status = 'paid';
                        $createdTransactions[] = Transaction::create([
                            'transaction_type' => 'income',
                            'category' => 'iuran_satpam',
                            'amount' => $invoice->security_bill,
                            'transaction_date' => $paymentDate,
                            'description' => "Iuran Satpam Bulan {$invoice->month}/{$invoice->year} - Rumah {$houseNo}",
                            'invoice_id' => $invoice->id,
                        ]);
                    }

                    $invoice->save();
                    $processedCount++;
                }
            }
        });

        return $this->successResponse([
            'processed_invoices' => $processedCount,
            'transactions_created' => count($createdTransactions),
        ], 'Pembayaran iuran berhasil diproses');
    }
}
