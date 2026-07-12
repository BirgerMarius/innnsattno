<?php

namespace App\Http\Controllers;

use App\ProfessionalResource;
use App\ResourceCategory;
use App\Tag;
use Illuminate\Http\Request;

class ProfessionalResourceController extends Controller
{
    public function index(Request $request)
    {
        $selectedCategory = $request->query('category');
        $selectedMediaType = $request->query('media_type');
        $selectedTag = $request->query('tag');

        if ($selectedMediaType && ! array_key_exists($selectedMediaType, ProfessionalResource::MEDIA_TYPES)) {
            return redirect()
                ->route('professional-resources.index', $request->except('media_type'))
                ->with('warning', 'Ugyldig medietypefilter ble fjernet.');
        }

        $publishedResourceScope = function ($query) {
            $query->where('status', ProfessionalResource::STATUS_PUBLISHED)
                ->whereHas('category', function ($categoryQuery) {
                    $categoryQuery->where('is_active', true);
                });
        };

        $hasPublishedResources = ProfessionalResource::query()
            ->where($publishedResourceScope)
            ->exists();

        $categoryOptions = ResourceCategory::query()
            ->where('is_active', true)
            ->whereHas('professionalResources', function ($query) {
                $query->where('status', ProfessionalResource::STATUS_PUBLISHED);
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $mediaTypeValues = ProfessionalResource::query()
            ->where($publishedResourceScope)
            ->whereNotNull('media_type')
            ->distinct()
            ->orderBy('media_type')
            ->pluck('media_type')
            ->filter(function ($mediaType) {
                return array_key_exists($mediaType, ProfessionalResource::MEDIA_TYPES);
            })
            ->values();

        $mediaTypes = collect(ProfessionalResource::MEDIA_TYPES)
            ->only($mediaTypeValues->all())
            ->all();

        $tagOptions = Tag::query()
            ->whereHas('professionalResources', $publishedResourceScope)
            ->orderBy('name')
            ->get();

        $categories = ResourceCategory::query()
            ->where('is_active', true)
            ->whereHas('professionalResources', function ($query) {
                $query->where('status', ProfessionalResource::STATUS_PUBLISHED);
                $this->applyFilters($query);
            })
            ->with(['publishedResources' => function ($query) {
                $query->orderBy('sort_order')->orderBy('title');
                $this->applyFilters($query);
                $query->with('tags');
            }])
            ->when($selectedCategory, function ($query) use ($selectedCategory) {
                $query->where('slug', $selectedCategory);
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('professional-resources.index', [
            'categories' => $categories,
            'categoryOptions' => $categoryOptions,
            'mediaTypes' => $mediaTypes,
            'tagOptions' => $tagOptions,
            'selectedCategory' => $selectedCategory,
            'selectedMediaType' => $selectedMediaType,
            'selectedTag' => $selectedTag,
            'hasPublishedResources' => $hasPublishedResources,
        ]);
    }

    private function applyFilters($query): void
    {
        $query->when(request('media_type'), function ($query, $mediaType) {
            $query->where('media_type', $mediaType);
        });

        $query->when(request('tag'), function ($query, $tag) {
            $query->whereHas('tags', function ($tagQuery) use ($tag) {
                $tagQuery->where('slug', $tag);
            });
        });
    }
}
