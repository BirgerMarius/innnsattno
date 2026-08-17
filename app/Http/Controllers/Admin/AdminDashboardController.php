<?php

namespace App\Http\Controllers\Admin;

use App\FeedbackSubmission;
use App\Http\Controllers\Controller;
use App\NewsArticle;
use App\NewsSource;
use App\ProfessionalResource;
use App\ResourceCategory;
use App\Services\AdminStatisticsSummary;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request, AdminStatisticsSummary $statistics)
    {
        $statusCounts = ProfessionalResource::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $period = in_array($request->query('traffic_period'), ['1', '7', '30'], true)
            ? $request->query('traffic_period') : '7';
        $requestedDate = $request->query('traffic_date');
        $trafficDate = $this->validTrafficDate($requestedDate);

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
            'trafficPeriod' => $period,
            'trafficDate' => $trafficDate,
            'trafficDateInvalid' => $requestedDate !== null && $trafficDate === null,
        ]);
    }

    private function validTrafficDate($value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value, 'Europe/Oslo');

            return $date->format('Y-m-d') === $value ? $value : null;
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
