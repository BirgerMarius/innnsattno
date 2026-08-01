<?php

namespace App\Http\Controllers\Admin;

use App\FeedbackSubmission;
use App\Http\Controllers\Controller;
use App\NewsArticle;
use App\NewsSource;
use App\ProfessionalResource;
use App\ResourceCategory;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $statusCounts = ProfessionalResource::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('admin.dashboard', [
            'publishedCount' => (int) ($statusCounts[ProfessionalResource::STATUS_PUBLISHED] ?? 0),
            'draftCount' => (int) ($statusCounts[ProfessionalResource::STATUS_DRAFT] ?? 0),
            'activeCategoryCount' => ResourceCategory::where('is_active', true)->count(),
            'pendingNewsCount' => NewsArticle::where('status', NewsArticle::STATUS_PENDING)->count(),
            'activeNewsSourceCount' => NewsSource::where('is_active', true)->count(),
            'newFeedbackCount' => FeedbackSubmission::where(function ($query) {
                $query->where('status', 'new')->orWhereNull('status');
            })->count(),
        ]);
    }
}
