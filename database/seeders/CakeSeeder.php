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
                'name' => 'Whole Wheat bread',
                'description' => 'It is a brown bread variety prepared using flour, partially or wholly milled from whole or near-to-whole wheat grains.',
                'price' => 150.00,
                'image' => 'whole wheat bread.webp',
                'is_featured' => false,
            ],
            [
                'name' => 'Pink Velvet',
                'description' => 'A soft, vibrant pink cake with a velvety texture and creamy frosting. Playful and chic for special moments.',
                'price' => 450.00,
                'image' => 'pink velvet.jpg',
                'is_featured' => true,
            ],
            [
                'name' => 'Brioche',
                'description' => 'It is a French pastry-type bread having a light, soft, and fluffy texture due to the increased amount of eggs and butter that goes into its preparation.',
                'price' => 75.00,
                'image' => 'Brioche.jpg',
                'is_featured' => false,
            ],
            [
                'name' => 'Croissants',
                'description' => 'The croissant is a Viennoiserie pastry originated in Austria, made with butter and yeast-leavened dough. ',
                'price' => 85.00,
                'image' => 'Croissants.jpg',
                'is_featured' => false,
            ],
            [
                'name' => 'Japanese Strawberry Cake',
                'description' => 'A light, fluffy sponge cake layered with fresh strawberries and airy whipped cream, often adorned with delicate fruit slices. ',
                'price' => 799.00,
                'image' => 'japanese strawberry cake.jpg',
                'is_featured' => true,
            ],
            [
                'name' => 'Chocolate Strawberry Cake',
                'description' => 'Rich chocolate layers paired with fresh, juicy strawberries and creamy frosting.',
                'price' => 899.00,
                'image' => 'chocolate strawberry cake.jpg',
                'is_featured' => true,
            ],
            [
                'name' => 'Forcaccia',
                'description' => 'A flatbread, baked in the oven, Focaccia has its roots in Italy. It closely replicates a pizza dough in its style and texture.',
                'price' => 200.00,
                'image' => 'Focaccia.jpg',
                'is_featured' => false,
            ],
            [
                'name' => 'Mutigrain bread',
                'description' => 'This bread variety uses two or more grains, with barley, millet, oats, flax, whole-wheat flour, and wheat being the common ones.  ',
                'price' => 150.00,
                'image' => 'mutigrain bread.webp',
                'is_featured' => false, 
            ],
            [
                'name' => 'Vanilla-Scented Cornmel Cake',
                'description' => 'Rustic cake with a subtle cornmeal crunch and warm vanilla aroma. ',
                'price' => 735.00,
                'image' => 'vanilla-scented cornmel cake.jpg',
                'is_featured' => false, 
            ],
            [
                'name' => 'Christmas Cake',
                'description' => 'Festive sponge cake with  decorated with Santa and christmas tree.',
                'price' => 799.00,
                'image' => 'christmas cake.png',
                'is_featured' => false,
            ],
                [
                'name' => 'Cornbread',
                'description' => 'This is a kind of quick bread, with cornmeal as the primary component mostly leavened using baking powder. ',
                'price' => 100.00,
                'image' => 'Cornbread.jpg',
                'is_featured' => true,
            ],
            [
                'name' => 'Duet Cake (white and black forest)',
                'description' => 'Half-and-half delight: white forest with creamy vanilla and cherries, black forest with chocolate and dark cherries.',
                'price' => 675.00,
                'image' => 'duet cake (white and black forest).jpg',
                'is_featured' => true,
            ],
            [
                'name' => 'Mothers Day Cake',
                'description' => 'A delicate cake in white with chocolate, decorated with edible flowers or hearts. Sweetly honors moms with love.',
                'price' => 699.00,
                'image' => 'mothers day cake.jpg',
                'is_featured' => false,
            ],
            [
                'name' => 'Birthday Cake',
                'description' => 'Classic, customizable cake with oreo and chocolate cream..',
                'price' => 769.00,
                'image' => 'birthday cake.png',
                'is_featured' => false,
            ],
            [
                'name' => 'Grissini',
                'description' => 'Grissini or breadsticks are long, crispy sticks originating in Italy, also known as dipping sticks, and grissino.',
                'price' => 100.00,
                'image' => 'Grissini bread.jpg',
                'is_featured' => true,
            ],
             [
                'name' => 'Sourdough bread',
                'description' => 'It is made by fermenting the dough with yeast and naturally occurring lactobacilli.',
                'price' => 75.00,
                'image' => 'sourdough bread.jpg',
                'is_featured' => false,
            ],
            [
                'name' => 'Cake Gojo Anime',
                'description' => 'A dynamic anime-inspired cake featuring Gojo Satoru.',
                'price' => 799.00,
                'image' => 'cake-gojo.jpg',
                'is_featured' => false,
            ],
            [
                'name' => 'Chicken bagel',
                'description' => 'Bagel or beigel, is a ring-shaped bread, originating in Poland’s Jewish communities..',
                'price' => 135.00,
                'image' => 'Chicken bagel.jpg',
                'is_featured' => false,
            ],
        ];

        foreach ($cakes as $cake) {
            Cake::create($cake);
        }
    }
}
