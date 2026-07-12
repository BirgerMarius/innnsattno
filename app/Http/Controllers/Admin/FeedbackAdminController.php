<?php

namespace App\Http\Controllers\Admin;

use App\FeedbackSubmission;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFeedbackSubmissionRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeedbackAdminController extends Controller
{
    public const STATUSES = [
        'new' => 'Ny',
        'in_progress' => 'Under behandling',
        'completed' => 'Ferdig',
        'archived' => 'Arkivert',
    ];

    public function index(Request $request)
    {
        $requestedStatus = $request->query('status');

        if ($request->has('status') && (! is_string($requestedStatus) || ! array_key_exists($requestedStatus, self::STATUSES))) {
            return redirect()
                ->route('admin.feedback.index')
                ->with('warning', 'Ugyldig statusfilter ble fjernet.');
        }

        $activeStatus = $requestedStatus;
        $statusCounts = FeedbackSubmission::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();
        $feedbackSubmissions = FeedbackSubmission::latest();

        if ($activeStatus) {
            $feedbackSubmissions->where('status', $activeStatus);
        }

        return view('admin.feedback.index', [
            'feedbackSubmissions' => $feedbackSubmissions->get(),
            'feedbackTypes' => StoreFeedbackSubmissionRequest::TYPES,
            'statuses' => self::STATUSES,
            'activeStatus' => $activeStatus,
            'allStatusCount' => array_sum($statusCounts),
            'statusCounts' => $statusCounts,
        ]);
    }

    public function update(Request $request, FeedbackSubmission $feedbackSubmission)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'admin_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $feedbackSubmission->update($validated);

        return redirect()
            ->route('admin.feedback.index')
            ->with('success', 'Forslaget ble oppdatert.');
    }
}
