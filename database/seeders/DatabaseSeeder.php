<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('transactions')->truncate();
        DB::table('invoices')->truncate();
        DB::table('house_histories')->truncate();
        DB::table('residents')->truncate();
        DB::table('houses')->truncate();
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        \App\Models\User::create([
    'name' => 'Admin RT',
    'email' => 'admin@rt.com',
    'password' => bcrypt('password123'),
]);

        $houseIds = [];
        for ($i = 1; $i <= 20; $i++) {
            $uuid = (string) Str::uuid();
            $houseIds[] = $uuid;
            
            $status = ($i <= 15) ? 'occupied' : 'vacant';
            
            DB::table('houses')->insert([
                'id' => $uuid,
                'house_number' => 'A-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'house_status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $residentData = [
            ['fullname' => 'Budi Santoso', 'status' => 'settler', 'phone' => '081234567890', 'marriage' => 'married'],
            ['fullname' => 'Siti Aminah', 'status' => 'settler', 'phone' => '081234567891', 'marriage' => 'married'],
            ['fullname' => 'Agus Pratama', 'status' => 'settler', 'phone' => '081234567892', 'marriage' => 'married'],
            ['fullname' => 'Dewi Lestari', 'status' => 'settler', 'phone' => '081234567893', 'marriage' => 'single'],
            ['fullname' => 'Eko Prasetyo', 'status' => 'settler', 'phone' => '081234567894', 'marriage' => 'married'],
            ['fullname' => 'Fajar Nugraha', 'status' => 'settler', 'phone' => '081234567895', 'marriage' => 'married'],
            ['fullname' => 'Gita Gutawa', 'status' => 'settler', 'phone' => '081234567896', 'marriage' => 'single'],
            ['fullname' => 'Hendra Wijaya', 'status' => 'settler', 'phone' => '081234567897', 'marriage' => 'married'],
            ['fullname' => 'Indah Permata', 'status' => 'settler', 'phone' => '081234567898', 'marriage' => 'single'],
            ['fullname' => 'Joko Widodo', 'status' => 'settler', 'phone' => '081234567899', 'marriage' => 'married'],
            ['fullname' => 'Kurniawan Dwi', 'status' => 'settler', 'phone' => '081234567900', 'marriage' => 'married'],
            ['fullname' => 'Lani Wijaya', 'status' => 'settler', 'phone' => '081234567901', 'marriage' => 'single'],
            ['fullname' => 'Mulyadi', 'status' => 'settler', 'phone' => '081234567902', 'marriage' => 'married'],
            ['fullname' => 'Nia Ramadhani', 'status' => 'settler', 'phone' => '081234567903', 'marriage' => 'married'],
            ['fullname' => 'Oki Setiana', 'status' => 'settler', 'phone' => '081234567904', 'marriage' => 'married'],
            ['fullname' => 'Rian D\'Masiv', 'status' => 'temporary', 'phone' => '081234567905', 'marriage' => 'single'],
            ['fullname' => 'Siska Kohl', 'status' => 'temporary', 'phone' => '081234567906', 'marriage' => 'married'],
            ['fullname' => 'Tulus', 'status' => 'temporary', 'phone' => '081234567907', 'marriage' => 'single'],
        ];

        $residentIds = [];
        foreach ($residentData as $res) {
            $uuid = (string) Str::uuid();
            $residentIds[] = $uuid;
            
            DB::table('residents')->insert([
                'id' => $uuid,
                'fullname' => $res['fullname'],
                'ktp_image' => 'ktp/default_ktp.jpg',
                'resident_status' => $res['status'],
                'phone_number' => $res['phone'],
                'marriage_status' => $res['marriage'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        for ($i = 0; $i < 15; $i++) {
            DB::table('house_histories')->insert([
                'id' => (string) Str::uuid(),
                'house_id' => $houseIds[$i],
                'resident_id' => $residentIds[$i],
                'start_date' => Carbon::now()->subMonths(12)->format('Y-m-d'),
                'end_date' => null, 
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('house_histories')->insert([
            'id' => (string) Str::uuid(),
            'house_id' => $houseIds[15],
            'resident_id' => $residentIds[15],
            'start_date' => Carbon::now()->subMonths(6)->format('Y-m-d'),
            'end_date' => Carbon::now()->subMonths(1)->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $currentYear = date('Y');
        
        for ($month = 1; $month <= 12; $month++) {
            for ($i = 0; $i < 15; $i++) {
                $invoiceId = (string) Str::uuid();
                $isPaid = ($month <= 10) ? 'paid' : 'unpaid';
                
                DB::table('invoices')->insert([
                    'id' => $invoiceId,
                    'house_id' => $houseIds[$i],
                    'resident_id' => $residentIds[$i],
                    'month' => $month,
                    'year' => $currentYear,
                    'cleaning_bill' => 15000,
                    'security_bill' => 100000,
                    'cleaning_bill_status' => $isPaid,
                    'security_bill_status' => $isPaid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($isPaid === 'paid') {
                    $transDate = Carbon::create($currentYear, $month, rand(1, 10))->format('Y-m-d');
                    
                    DB::table('transactions')->insert([
                        'id' => (string) Str::uuid(),
                        'transaction_type' => 'income',
                        'category' => 'iuran_kebersihan',
                        'amount' => 15000,
                        'transaction_date' => $transDate,
                        'description' => "Iuran Kebersihan Bulan {$month}/{$currentYear} - Rumah A-" . str_pad($i+1, 2, '0', STR_PAD_LEFT),
                        'invoice_id' => $invoiceId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('transactions')->insert([
                        'id' => (string) Str::uuid(),
                        'transaction_type' => 'income',
                        'category' => 'iuran_satpam',
                        'amount' => 100000,
                        'transaction_date' => $transDate,
                        'description' => "Iuran Satpam Bulan {$month}/{$currentYear} - Rumah A-" . str_pad($i+1, 2, '0', STR_PAD_LEFT),
                        'invoice_id' => $invoiceId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $expenseDate = Carbon::create($currentYear, $month, 25)->format('Y-m-d');
            
            DB::table('transactions')->insert([
                'id' => (string) Str::uuid(),
                'transaction_type' => 'expenses',
                'category' => 'gaji_satpam',
                'amount' => 1200000,
                'transaction_date' => $expenseDate,
                'description' => "Gaji Satpam Pos RT Bulan {$month}/{$currentYear}",
                'invoice_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('transactions')->insert([
                'id' => (string) Str::uuid(),
                'transaction_type' => 'expenses',
                'category' => 'token_listrik',
                'amount' => 150000,
                'transaction_date' => $expenseDate,
                'description' => "Token Listrik Pos Satpam Bulan {$month}/{$currentYear}",
                'invoice_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($month == 3) {
                DB::table('transactions')->insert([
                    'id' => (string) Str::uuid(),
                    'transaction_type' => 'expenses',
                    'category' => 'perbaikan_selokan',
                    'amount' => 350000,
                    'transaction_date' => Carbon::create($currentYear, $month, 15)->format('Y-m-d'),
                    'description' => "Biaya Pembersihan & Perbaikan Selokan Blok A",
                    'invoice_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($month == 7) {
                DB::table('transactions')->insert([
                    'id' => (string) Str::uuid(),
                    'transaction_type' => 'expenses',
                    'category' => 'perbaikan_jalan',
                    'amount' => 500000,
                    'transaction_date' => Carbon::create($currentYear, $month, 10)->format('Y-m-d'),
                    'description' => "Biaya Penambalan Jalan Berlubang",
                    'invoice_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
