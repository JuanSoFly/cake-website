<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Display the home page.
     */
    public function index()
    {
        $cakes = \App\Models\Cake::all();
        
        $testimonials = [
            [
                'name' => 'Maria Santos',
                'rating' => 5,
                'comment' => 'The Japanese Strawberry Cake was so light and delicious! Perfect for my daughter\'s birthday.',
                'initials' => 'MS'
            ],
            [
                'name' => 'Juan Dela Cruz',
                'rating' => 5,
                'comment' => 'Best chocolate cake I\'ve had in a long time. Definitely worth every peso.',
                'initials' => 'JD'
            ],
            [
                'name' => 'Elena Reyes',
                'rating' => 4,
                'comment' => 'I love their whole wheat bread. It\'s fresh and healthy. Will buy again!',
                'initials' => 'ER'
            ]
        ];

        return view('home', compact('cakes', 'testimonials'));
    }
}
