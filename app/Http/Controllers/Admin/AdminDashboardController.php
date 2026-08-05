<?php

namespace App\Http\Controllers\Admin;

use App\FeedbackSubmission;
use App\Http\Controllers\Controller;
use App\NewsArticle;
use App\NewsSource;
use App\ProfessionalResource;
use App\ResourceCategory;
use App\Services\AdminStatisticsSummary;

class AdminDashboardController extends Controller
{
    public function index(AdminStatisticsSummary $statistics)
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
            'newsSourceErrorCount' => NewsSource::where('is_active', true)
                ->whereNotNull('last_error')
                ->where('last_error', '<>', '')
                ->count(),
            'newFeedbackCount' => FeedbackSubmission::where(function ($query) {
                $query->where('status', 'new')->orWhereNull('status');
            })->count(),
            'statistics' => $statistics->read(),
        ]);
    }
}
