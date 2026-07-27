<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\Invoice;
use App\Models\Resident;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use ApiResponse;

    public function summary(Request $request): JsonResponse
    {
        $year = (int) ($request->input('year') ?? Carbon::now()->year);

        $monthlyData = Transaction::select(
            DB::raw('MONTH(transaction_date) as month'),
            DB::raw("SUM(CASE WHEN transaction_type = 'income' THEN amount ELSE 0 END) as total_income"),
            DB::raw("SUM(CASE WHEN transaction_type = 'expenses' THEN amount ELSE 0 END) as total_expenses")
        )
        ->whereYear('transaction_date', $year)
        ->groupBy(DB::raw('MONTH(transaction_date)'))
        ->get()
        ->keyBy('month');

        $months = [];
        $annualIncome = 0;
        $annualExpenses = 0;

        for ($m = 1; $m <= 12; $m++) {
            $income = (float) ($monthlyData->get($m)->total_income ?? 0);
            $expenses = (float) ($monthlyData->get($m)->total_expenses ?? 0);
            $balance = $income - $expenses;

            $annualIncome += $income;
            $annualExpenses += $expenses;

            $months[] = [
                'month' => $m,
                'month_name' => Carbon::create(null, $m, 1)->translatedFormat('F'),
                'income' => $income,
                'expenses' => $expenses,
                'balance' => $balance,
            ];
        }

        $totalHouses = House::count();
        $occupiedHouses = House::where('house_status', 'occupied')->count();
        $vacantHouses = House::where('house_status', 'vacant')->count();

        $totalResidents = Resident::count();
        $settlerResidents = Resident::where('resident_status', 'settler')->count();
        $temporaryResidents = Resident::where('resident_status', 'temporary')->count();

        $unpaidInvoicesCount = Invoice::where('cleaning_bill_status', 'unpaid')
            ->orWhere('security_bill_status', 'unpaid')
            ->count();

        $totalIncomeAllTime = (float) Transaction::where('transaction_type', 'income')->sum('amount');
        $totalExpensesAllTime = (float) Transaction::where('transaction_type', 'expenses')->sum('amount');
        $netBalanceAllTime = $totalIncomeAllTime - $totalExpensesAllTime;

        return $this->successResponse([
            'year' => $year,
            'monthly_summary' => $months,
            'annual_income' => $annualIncome,
            'annual_expenses' => $annualExpenses,
            'annual_net_balance' => $annualIncome - $annualExpenses,
            'dashboard' => [
                'total_houses' => $totalHouses,
                'occupied_houses' => $occupiedHouses,
                'vacant_houses' => $vacantHouses,
                'total_residents' => $totalResidents,
                'settler_residents' => $settlerResidents,
                'temporary_residents' => $temporaryResidents,
                'unpaid_invoices_count' => $unpaidInvoicesCount,
                'total_income_all_time' => $totalIncomeAllTime,
                'total_expenses_all_time' => $totalExpensesAllTime,
                'net_balance_all_time' => $netBalanceAllTime,
            ]
        ], "Laporan ringkasan tahun {$year} berhasil diambil");
    }
}
