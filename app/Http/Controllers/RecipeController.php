<?php

namespace App\Http\Controllers;

use App\Recipes;
use Illuminate\Http\Request;
use App\Services\RecipeService;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\File;

class RecipeController extends Controller
{
    protected $recipeService;

    public function __construct(RecipeService $recipeService)
    {
        $this->recipeService = $recipeService;
    }

    public function getRecipePDF($id)
    {
        $recipe = Recipes::findOrFail($id);
        $recipeData = $this->recipeService->parseRecipeData($recipe);

        $PDFView = view('recipe', $recipeData);
        $pdf = PDF::loadHTML($PDFView);

        return $pdf->stream();
    }

    public function downloadRecipePDF($id)
    {
        $recipe = Recipes::findOrFail($id);
        $recipeData = $this->recipeService->parseRecipeData($recipe);

        $pdf = PDF::loadView('recipe', $recipeData);
        $recipePDFName = config('app.name') . ".Recipe.pdf";

        return $pdf->download($recipePDFName);
    }

    protected function getBase64ApplicationLogo()
    {
        $applicationImagePath =  public_path('logo.jpg');
        $logoMime = pathinfo($applicationImagePath, PATHINFO_EXTENSION);

        $dataImage = file_get_contents($applicationImagePath);
        $base64 = 'data:image/' . $logoMime . ';base64,' . base64_encode($dataImage);

        return $base64;
    }
}
