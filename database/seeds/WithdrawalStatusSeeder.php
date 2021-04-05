<?php

use Illuminate\Database\Seeder;
use App\Models\WithdrawalStatus;

class WithdrawalStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        WithdrawalStatus::create([
            'name' => 'Requested',
        ]);

        WithdrawalStatus::create([
            'name' => 'Approved',
        ]);
    }
}
