<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'title' => 'Weekend Cart Festival',
                'subtitle' => 'Special offers from evening food carts.',
                'cta_label' => 'Explore Sellers',
                'cta_link' => '#sellers',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Food Court Combo Week',
                'subtitle' => 'Order from multiple stalls in one checkout.',
                'cta_label' => 'See Menu',
                'cta_link' => '#menu',
                'sort_order' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($items as $item) {
            Banner::updateOrCreate(
                ['title' => $item['title']],
                $item
            );
        }
    }
}
