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
                'name' => 'Chocolate Dedication Cake!',
                'description' => 'Enjoy our bestselling chocolate-rich and candy-colorful cake',
                'price' => 450.00,
                'image' => 'bday-cake.jpg',
                'is_featured' => true,
            ],
            [
                'name' => 'Supreme Caramel-Mocha Dedication Cake!',
                'description' => 'Give a supreme gift for a supreme mom 💝 Show your love for mom ',
                'price' => 599.00,
                'image' => 'Supreme Caramel-Mocha Dedication Cake!.jpg',
                'is_featured' => true,
            ],
            [
                'name' => 'Fudyy Brownies',
                'description' => 'Gift Mom the perfect choco-licious treat with our Fudgy Brownies!',
                'price' => 169.00,
                'image' => 'fudyy brownies.jpg',
                'is_featured' => true,
            ],
            [
                'name' => 'Ensaimada',
                'description' => 'Enjoy our Cheesy Ensaimada! Topped with lots of delicious cheese and butter-cream – truly a cheesy-sweet treat! ',
                'price' => 35.00,
                'image' => 'ensaimada.jpg',
                'is_featured' => false, 
            ],
            [
                'name' => 'Bundle 3',
                'description' => 'Choose Regular black forest cake or chocolate dedication cake',
                'price' => 799.00,
                'image' => 'bundle.jpg',
                'is_featured' => false,
            ],
            [
                'name' => 'Dedication cake',
                'description' => 'Top up the birthday fun with the NEW Princess and Cars Theme Toppers that can be added on any Red Ribbon Dedication Cake!',
                'price' => 675.00,
                'image' => 'dedication cakes.jpg',
                'is_featured' => true,
            ],
            [
                'name' => 'Black Forest',
                'description' => 'A delectable Black Forest cake to make your holiday even sweeter!',
                'price' => 569.00,
                'image' => 'black forest.jpg',
                'is_featured' => false,
            ],
            [
                'name' => 'Red Velvet',
                'description' => ' A delectable Red Velvet Bliss cake to make your holiday even sweeter!',
                'price' => 569.00,
                'image' => 'red velvet.jpg',
                'is_featured' => false,
            ],
            [
                'name' => 'Chicken Empanada',
                'description' => 'Chicken Empanada: Taste Home in Every Bite! Grab yours',
                'price' => 55.00,
                'image' => 'empanada.jpg',
                'is_featured' => false,
            ],
        ];

        foreach ($cakes as $cake) {
            Cake::create($cake);
        }
    }
}
