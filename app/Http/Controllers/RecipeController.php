<?php

namespace App\Http\Controllers;

use App\Recipes;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade as PDF;

class RecipeController extends Controller
{
    public function __construct()
    {
        
    }

    public function getRecipePDF($id)
    {
        $recipe = Recipes::findOrFail($id);

        // return view('recipe', $recipe);

        $data = ['title' => 'Welcome to HDTuto.com'];
        $pdf = PDF::loadView('recipe', $data);
  
        return $pdf->download('itsolutionstuff.pdf');

    }

    public function downloadRecipePDF($id)
    {
        $recipe = Recipes::findOrFail($id);
    }
}
