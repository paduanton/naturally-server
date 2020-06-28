<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class TermsController extends Controller
{
    public function index()
    {
        $termsPath = public_path('/terms/terms.pdf');

        if (!File::exists($termsPath)) {
            abort(404);
        }

        $termsFile = File::get($termsPath);
        $mimeType = File::mimeType($termsPath);
        
        $response = Response::make($termsFile, 200);
        $response->header('Content-Type', $mimeType);
        $response->header('Content-Disposition', 'filename="naturally.termsOfUse.pdf"');

        return $response;
    }
}
