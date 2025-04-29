<?php

namespace App\Http\Controllers;

use App\Models\Cake;

abstract class Controller
{
    public function index()
    {
        // Get featured cakes for the homepage
        $cakes = Cake::where('is_featured', true)->take(6)->get();

        // Sample testimonial data
        $testimonials = [
            [
                'name' => 'Sarah Johnson',
                'initials' => 'SJ',
                'comment' => 'The birthday cake you made for my daughter was absolutely perfect! Not only was it beautiful, but it tasted amazing too. Everyone at the party was impressed.',
                'rating' => 5,
            ],
            [
                'name' => 'Michael Chen',
                'initials' => 'MC',
                'comment' => 'We ordered our wedding cake from Sweet Delights and couldn\'t be happier. The design process was fun, and the final cake exceeded our expectations. Thank you!',
                'rating' => 5,
            ],
            [
                'name' => 'Emily Rodriguez',
                'initials' => 'ER',
                'comment' => 'I\'ve tried many bakeries, but Sweet Delights is by far the best. Their cakes are moist, flavorful, and the decorations are always on point. My go-to for all celebrations!',
                'rating' => 5,
            ],
        ];

        return view('home', compact('cakes', 'testimonials'));
    }
}
