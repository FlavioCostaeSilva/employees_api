<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::table('currencies')->insert([
            'id' => 1,
            'code' => 'BRL',
            'code_name' => 'Real Brasileiro',
            'code_iso_lang' => 'pt_BR',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        DB::table('currencies')->insert([
            'id' => 2,
            'code' => 'USD',
            'code_name' => 'Dólar Americano',
            'code_iso_lang' => 'en_US',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        DB::table('currencies')->insert([
            'id' => 3,
            'code' => 'EUR',
            'code_name' => 'Euro',
            'code_iso_lang' => 'de_DE',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        DB::table('currencies')->insert([
            'id' => 4,
            'code' => 'GBP',
            'code_name' => 'Libra Esterlina',
            'code_iso_lang' => 'en_GB',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        DB::table('currencies')->insert([
            'id' => 5,
            'code' => 'CAD',
            'code_name' => 'Dólar Canadense',
            'code_iso_lang' => 'en_CA',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        DB::table('currency_pairs')->insert([
            'code_1' => 'USD',
            'code_2' => 'BRL',
            'currency_1' => 2,
            'currency_2' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        DB::table('currency_pairs')->insert([
            'code_1' => 'EUR',
            'code_2' => 'BRL',
            'currency_1' => 3,
            'currency_2' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        DB::table('currency_pairs')->insert([
            'code_1' => 'GBP',
            'code_2' => 'BRL',
            'currency_1' => 4,
            'currency_2' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        DB::table('currency_pairs')->insert([
            'code_1' => 'CAD',
            'code_2' => 'BRL',
            'currency_1' => 5,
            'currency_2' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }
}
