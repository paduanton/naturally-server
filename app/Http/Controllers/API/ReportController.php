<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Users;
use App\Reports;
use Illuminate\Http\Request;
use App\Notifications\Report;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReportResource;
use Illuminate\Foundation\Auth\User;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $this->validate($request, [
            'users_id' => 'nullable|numeric|integer|exists:App\Users,id',
            'title' => 'required|string|between:5,60',
            'description' => 'required|string|between:10,255',
            'category' => [
                'required',
                'string',
                Rule::in(['bug', 'suggestion', 'idea', 'partnership', 'something_else', 'application_improvement']),
            ],
            'who_reported' => 'required_without:users_id|string',
            'email' => 'required_without:users_id|email'
        ]);

        $report = Reports::create($request->all());

        if (isset($request['users_id'])) {
            $user = Users::find($request['users_id']);
        }

        $content = [
            'title' => $report->title,
            'description' => $report->description,
            'category' => $report->category,
            'who_reported' => $report->who_reported ?? null,
            'email' => $report->email ?? null,
            'user' => $user ?? null
        ];

        $notifyAuthor = $this->notifyAuthor($content);

        if ($report && $notifyAuthor) {
            return new ReportResource($report);
        }

        return response()->json([
            'message' => 'could not store data'
        ], 400);
    }

    protected function notifyAuthor(array $content)
    {
        $author = Users::getAuthorAccount();

        try {
            $author->notify(new Report($content));
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }
}
