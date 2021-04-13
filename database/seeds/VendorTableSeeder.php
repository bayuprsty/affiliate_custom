<?php

use Illuminate\Database\Seeder;
use App\Models\Vendor;

class VendorTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Vendor::create([
            'name' => 'Akela',
            'link' => 'https://tradingnyantai.com',
            'link_embed' => 'https://tradingnyantai.com',
            'marketing_text' => 'https://tradingnyantai.com',
            'no_telepon' => '081235158353',
            'email' => 'tradingnyantai@gmail.com',
            'secret_id' => Str::random(30),
        ]);
    }
}
