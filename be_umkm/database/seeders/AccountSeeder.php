<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            // Aktiva
            ['code'=>'101','name'=>'Kas','type'=>'asset','is_cash'=>true],
            ['code'=>'102','name'=>'Piutang Usaha','type'=>'asset'],
            ['code'=>'103','name'=>'Peralatan','type'=>'asset'],
            ['code'=>'104','name'=>'Perlengkapan','type'=>'asset'],

            // Pasiva (Kewajiban)
            ['code'=>'201','name'=>'Utang Usaha','type'=>'liability'],
            ['code'=>'202','name'=>'Utang Bank','type'=>'liability'],

            // Pasiva (Modal)
            ['code'=>'301','name'=>'Modal Awal','type'=>'equity'],
            ['code'=>'302','name'=>'Prive','type'=>'equity'],
            ['code'=>'303','name'=>'Dividen','type'=>'equity'],

            // Pendapatan
            ['code'=>'401','name'=>'Pendapatan Penjualan','type'=>'revenue'],
            ['code'=>'402','name'=>'Pendapatan Jasa','type'=>'revenue'],

            // Beban
            ['code'=>'501','name'=>'Beban Gaji','type'=>'expense'],
            ['code'=>'502','name'=>'Beban Listrik','type'=>'expense'],
            ['code'=>'503','name'=>'Beban Sewa','type'=>'expense'],
        ];

        foreach ($accounts as $acc) {
            Account::create($acc);
        }
    }
}
