<?php

namespace App\Http\Controllers;

use Exception;
use App\Recipes;
use App\PDFDownloads;
use Illuminate\Http\Request;
use App\Services\RecipeService;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\Auth;

class RecipeController extends Controller
{
    protected $recipeService;

    public function __construct(RecipeService $recipeService)
    {
        $this->recipeService = $recipeService;
    }

    public function getRecipePDF(Request $request, $id)
    {
        try {
            $recipe = Recipes::findOrFail($id);
            $recipeData = $this->recipeService->parseRecipeData($recipe);

            $PDFView = view('recipe', $recipeData);
            $pdf = PDF::loadHTML($PDFView);
        } catch (Exception $exception) {
            return redirect('/v1/notfound');
        }

        $downloadLog = [
            'users_id' => $request->user('api')->id ?? null,
            'recipes_id' => $recipe->id,
            'ip' => $request->ip() . ':' . $_SERVER['REMOTE_PORT'],
            'user_agent' => $request->header('User-Agent'),
            'created_at' => now()
        ];

        PDFDownloads::create($downloadLog);

        return $pdf->stream();
    }

    public function downloadRecipePDF(Request $request, $id)
    {
        try {
            $recipe = Recipes::findOrFail($id);
            $recipeData = $this->recipeService->parseRecipeData($recipe);

            $pdf = PDF::loadView('recipe', $recipeData);
            $recipePDFName = config('app.name') . ".Recipe.pdf";
        } catch (Exception $exception) {
            return redirect('/v1/notfound');
        }

        $downloadLog = [
            'users_id' => $request->user('api')->id ?? null,
            'recipes_id' => $recipe->id,
            'ip' => $request->ip() . ':' . $_SERVER['REMOTE_PORT'],
            'user_agent' => $request->header('User-Agent'),
            'created_at' => now()
        ];

        PDFDownloads::create($downloadLog);
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
