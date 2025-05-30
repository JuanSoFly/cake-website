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
                'name' => 'Reniele Rabanillo',
                'initials' => 'RR',
                'comment' => '7/10 sakin xmas cake wala naman kaming xmas ang boring ni kuya arn at otatay mga shity sa gilid pero 7/10 pa rin sila sakin',
                'rating' => 7,
            ],
            [
                'name' => 'Rodney Onias',
                'initials' => 'RO',
                'comment' => 'Nakaka tampo yung cashier hindi ako pinansin sabi ko pabili ng malboro',
                'rating' => 3,
            ],
            [
                'name' => 'Sebastersers Barcia',
                'initials' => 'SB',
                'comment' => 'Yeheyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy!!!!!!',
                'rating' => 5,
            ],
             [
                'name' => 'Kuya Arn Kenneth',
                'initials' => 'AK',
                'comment' => 'Sobrang sarap naman nitong Japanese Straberry Cakes kakabili ko kay Reniele Rabanillo ',
                'rating' => 5,
            ],
             [
                'name' => 'Otatay',
                'initials' => 'OT',
                'comment' => 'Okay naman ang gojo cake kaso hindi ko abot yung cashier kaya binag ko na lang 5/5 pa rin kayo kasi hindi niyo ako hinabol',
                'rating' => 5,
            ],
             [
                'name' => 'Francis Roi Paladan',
                'initials' => 'FR',
                'comment' => 'Namatay si Pope Francis ako na ang papalit sa kanya sana iboto niyo ako',
                'rating' => 5,
            ],
        ];

        return view('home', compact('cakes', 'testimonials'));
    }
}
