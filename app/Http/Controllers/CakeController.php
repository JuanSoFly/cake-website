<?php

namespace App\Http\Controllers;

use App\Models\Cake;
use Illuminate\Http\Request;

class CakeController extends Controller
{
     /**
     * Display a listing of the cakes.
     */
    public function index()
    {
        $cakes = Cake::all();
        return view('cakes.index', compact('cakes'));
    }

    /**
     * Display the specified cake.
     */
    public function show(Cake $cake)
    {
        return view('cakes.show', compact('cake'));
    }
}
