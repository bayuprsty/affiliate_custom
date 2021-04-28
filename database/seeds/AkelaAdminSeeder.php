<?php

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AkelaAdminSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'username' => 'akela',
            'nama_depan' => 'Administrator',
            'nama_belakang' => 'System',
            'email' => 'tradingnyantai@gmail.com',
            'password' => bcrypt('AkelaAff777'),
            'role' => 'admin',
            'join_date' => Carbon::NOW(),
            'email_confirmed' => true,
            'email_verified_at' => Carbon::NOW(),
        ]);
    }
}
