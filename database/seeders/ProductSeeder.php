<?php

namespace Database\Seeders;

use App\Models\Stock;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Product::factory()->count(20)->create()->each(function($p) {
            $count = rand(1,3);
            for ($i=0; $i<$count; $i++) {
                Stock::create([
                    'product_id' => $p->id,
                    'sku' => 'SKU-'.strtoupper(substr(md5(uniqid($p->id,true)),0,6)).rand(10,99),
                    'sale_price' => rand(50,500),
                    'purchase_price' => rand(30,400),
                    'quantity' => rand(1,30),
                    'last_update_at' => now(),
                ]);
            }
        });
    }
}
