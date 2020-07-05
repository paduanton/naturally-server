<?php

namespace App\Http\Controllers;

use PDF;
use App\Recipes;
use Illuminate\Http\Request;

class RecipeController extends Controller
{

    public function getRecipePDF($id)
    {
        $recipe = Recipes::findOrFail($id);

        return view('recipe.pdf', $recipe);

        // $pdf->loadHTML('<h1>Test</h1>');
        // return $pdf->stream();

        // $pdf = PDF::loadView('pdf.invoice', $data);
        // return $pdf->download('invoice.pdf');
    }

    public function downloadRecipePDF($id)
    {
        $recipe = Recipes::findOrFail($id);
    }
}
