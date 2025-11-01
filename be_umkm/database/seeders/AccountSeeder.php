<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // Aset
            ['code'=>'101','name'=>'Kas','type'=>'asset','is_cash'=>true,'normal_balance'=>'debit'],
            ['code'=>'102','name'=>'Piutang Usaha','type'=>'asset','normal_balance'=>'debit'],
            ['code'=>'103','name'=>'Peralatan','type'=>'asset','normal_balance'=>'debit'],
            ['code'=>'104','name'=>'Perlengkapan','type'=>'asset','normal_balance'=>'debit'],

            // Kewajiban
            ['code'=>'201','name'=>'Utang Usaha','type'=>'liability','normal_balance'=>'credit'],
            ['code'=>'202','name'=>'Utang Bank','type'=>'liability','normal_balance'=>'credit'],

            // Ekuitas
            ['code'=>'301','name'=>'Modal Awal','type'=>'equity','normal_balance'=>'credit'],
            ['code'=>'302','name'=>'Prive','type'=>'equity','normal_balance'=>'debit'],
            ['code'=>'303','name'=>'Dividen','type'=>'equity','normal_balance'=>'debit'],

            // Pendapatan
            ['code'=>'401','name'=>'Pendapatan Penjualan','type'=>'revenue','normal_balance'=>'credit'],
            ['code'=>'402','name'=>'Pendapatan Jasa','type'=>'revenue','normal_balance'=>'credit'],

            // Beban
            ['code'=>'501','name'=>'Beban Gaji','type'=>'expense','normal_balance'=>'debit'],
            ['code'=>'502','name'=>'Beban Listrik','type'=>'expense','normal_balance'=>'debit'],
            ['code'=>'503','name'=>'Beban Sewa','type'=>'expense','normal_balance'=>'debit'],
        ];

        foreach ($accounts as $account) {
            Account::updateOrCreate(['code' => $account['code']], $account);
        }
    }
}
