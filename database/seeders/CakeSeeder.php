<?php

namespace Database\Seeders;

use App\Models\Cake;
use Illuminate\Database\Seeder;

class CakeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cakes = [
            [
                'name' => 'Chocolate Delight',
                'description' => 'Rich chocolate layers with ganache and chocolate shavings',
                'price' => 45.00,
                'image' => 'chocolate-delight.png',
                'is_featured' => true,
            ],
            [
                'name' => 'Strawberry Dream',
                'description' => 'Light vanilla cake with fresh strawberries and cream',
                'price' => 48.00,
                'image' => 'strawberry-dream.jpg',
                'is_featured' => true,
            ],
            [
                'name' => 'Red Velvet',
                'description' => 'Classic red velvet with cream cheese frosting',
                'price' => 50.00,
                'image' => 'red-velvet.jpg',
                'is_featured' => true,
            ],
            [
                'name' => 'Lemon Bliss',
                'description' => 'Tangy lemon cake with lemon curd and buttercream',
                'price' => 46.00,
                'image' => 'lemon-bliss.jpg',
                'is_featured' => false,
            ],
            [
                'name' => 'Carrot Cake',
                'description' => 'Spiced carrot cake with walnuts and cream cheese frosting',
                'price' => 42.00,
                'image' => 'carrot-cake.jpg',
                'is_featured' => false,
            ],
            [
                'name' => 'Wedding Special',
                'description' => 'Elegant tiered cake customized for your special day',
                'price' => 120.00,
                'image' => 'wedding-special.jpg',
                'is_featured' => true,
            ],
            [
                'name' => 'Black Forest',
                'description' => 'Chocolate sponge with cherries and whipped cream',
                'price' => 52.00,
                'image' => 'black-forest.jpg',
                'is_featured' => false,
            ],
            [
                'name' => 'Vanilla Bean',
                'description' => 'Classic vanilla cake with vanilla bean buttercream',
                'price' => 40.00,
                'image' => 'vanilla-bean.jpg',
                'is_featured' => false,
            ],
            [
                'name' => 'Tiramisu Cake',
                'description' => 'Coffee-soaked layers with mascarpone cream',
                'price' => 55.00,
                'image' => 'tiramisu-cake.jpg',
                'is_featured' => false,
            ],
        ];

        foreach ($cakes as $cake) {
            Cake::create($cake);
        }
    }
}
