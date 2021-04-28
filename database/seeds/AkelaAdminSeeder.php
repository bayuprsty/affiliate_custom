<?php

use Illuminate\Database\Seeder;

class AkelaAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
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
