<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProfessionalResourceRequest;
use App\ProfessionalResource;
use App\ResourceCategory;
use App\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProfessionalResourceAdminController extends Controller
{
    public function index(Request $request)
    {
        $requestedStatus = $request->query('status');

        if ($request->has('status') && (! is_string($requestedStatus) || ! array_key_exists($requestedStatus, ProfessionalResource::STATUSES))) {
            return redirect()
                ->route('admin.professional-resources.index')
                ->with('warning', 'Ugyldig statusfilter ble fjernet.');
        }

        $resources = ProfessionalResource::with(['category', 'tags'])
            ->when($requestedStatus, function ($query) use ($requestedStatus) {
                $query->where('status', $requestedStatus);
            })
            ->orderBy('status')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $statusCounts = ProfessionalResource::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        return view('admin.professional-resources.index', [
            'resources' => $resources,
            'statuses' => ProfessionalResource::STATUSES,
            'mediaTypes' => ProfessionalResource::MEDIA_TYPES,
            'activeStatus' => $requestedStatus,
            'statusCounts' => $statusCounts,
            'allStatusCount' => array_sum($statusCounts),
        ]);
    }

    public function create()
    {
        return view('admin.professional-resources.create', [
            'resource' => new ProfessionalResource([
                'status' => ProfessionalResource::STATUS_DRAFT,
                'sort_order' => 0,
            ]),
            'categories' => $this->categories(),
            'statuses' => ProfessionalResource::STATUSES,
            'mediaTypes' => ProfessionalResource::MEDIA_TYPES,
        ]);
    }

    public function store(ProfessionalResourceRequest $request)
    {
        $resource = ProfessionalResource::create($this->validatedData($request));
        $this->syncTags($resource, $request->input('tags', ''));

        return redirect()
            ->route('admin.professional-resources.edit', $resource)
            ->with('success', 'Fagressursen ble opprettet.');
    }

    public function edit(ProfessionalResource $professionalResource)
    {
        $professionalResource->load('tags');

        return view('admin.professional-resources.edit', [
            'resource' => $professionalResource,
            'categories' => $this->categories(),
            'statuses' => ProfessionalResource::STATUSES,
            'mediaTypes' => ProfessionalResource::MEDIA_TYPES,
        ]);
    }

    public function update(ProfessionalResourceRequest $request, ProfessionalResource $professionalResource)
    {
        $professionalResource->update($this->validatedData($request, $professionalResource));
        $this->syncTags($professionalResource, $request->input('tags', ''));

        return redirect()
            ->route('admin.professional-resources.edit', $professionalResource)
            ->with('success', 'Fagressursen ble oppdatert.');
    }

    public function preview(ProfessionalResource $professionalResource)
    {
        $professionalResource->load(['category', 'tags']);

        return view('admin.professional-resources.preview', [
            'resource' => $professionalResource,
        ]);
    }

    public function publish(ProfessionalResource $professionalResource)
    {
        if (! $professionalResource->comment) {
            return redirect()
                ->route('admin.professional-resources.edit', $professionalResource)
                ->with('warning', 'Kommentar eller anbefaling må fylles ut før publisering.');
        }

        if (! $professionalResource->media_type) {
            return redirect()
                ->route('admin.professional-resources.edit', $professionalResource)
                ->with('warning', 'Medietype må velges før publisering.');
        }

        $professionalResource->update([
            'status' => ProfessionalResource::STATUS_PUBLISHED,
            'published_at' => $professionalResource->published_at ?: now(),
        ]);

        return redirect()
            ->route('admin.professional-resources.index')
            ->with('success', 'Fagressursen ble publisert.');
    }

    public function unpublish(ProfessionalResource $professionalResource)
    {
        $professionalResource->update([
            'status' => ProfessionalResource::STATUS_DRAFT,
            'published_at' => null,
        ]);

        return redirect()
            ->route('admin.professional-resources.index')
            ->with('success', 'Fagressursen ble avpublisert.');
    }

    public function destroy(ProfessionalResource $professionalResource)
    {
        $professionalResource->delete();

        return redirect()
            ->route('admin.professional-resources.index')
            ->with('success', 'Fagressursen ble slettet.');
    }

    private function categories()
    {
        return ResourceCategory::orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function validatedData(ProfessionalResourceRequest $request, ?ProfessionalResource $resource = null): array
    {
        $data = $request->validated();
        unset($data['tags']);

        $data['is_featured'] = $request->boolean('is_featured');
        $data['media_type'] = $data['media_type'] ?? null;
        $data['media_type'] = $data['media_type'] ?: null;

        if ($data['status'] === ProfessionalResource::STATUS_PUBLISHED && (! $resource || ! $resource->published_at)) {
            $data['published_at'] = now();
        }

        if ($data['status'] === ProfessionalResource::STATUS_DRAFT) {
            $data['published_at'] = null;
        }

        return $data;
    }

    private function syncTags(ProfessionalResource $resource, ?string $tagText): void
    {
        $tagIds = collect(explode(',', (string) $tagText))
            ->map(function ($tag) {
                return trim($tag);
            })
            ->filter()
            ->map(function ($tag) {
                return Str::limit($tag, 80, '');
            })
            ->unique(function ($tag) {
                return Str::lower($tag);
            })
            ->map(function ($tag) {
                $slug = Str::slug($tag);

                if ($slug === '') {
                    $slug = Str::slug(Str::ascii($tag));
                }

                if ($slug === '') {
                    return null;
                }

                return Tag::firstOrCreate(
                    ['slug' => $slug],
                    ['name' => $tag]
                )->id;
            })
            ->filter()
            ->values()
            ->all();

        $resource->tags()->sync($tagIds);
    }
}
