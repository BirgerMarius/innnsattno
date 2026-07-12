<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResourceCategoryRequest;
use App\ResourceCategory;
use Illuminate\Support\Str;

class ResourceCategoryController extends Controller
{
    public function index()
    {
        $categories = ResourceCategory::withCount('professionalResources')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.resource-categories.index', [
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        return view('admin.resource-categories.create', [
            'category' => new ResourceCategory([
                'sort_order' => 0,
                'is_active' => true,
            ]),
        ]);
    }

    public function store(ResourceCategoryRequest $request)
    {
        ResourceCategory::create($this->validatedData($request));

        return redirect()
            ->route('admin.resource-categories.index')
            ->with('success', 'Kategorien ble opprettet.');
    }

    public function edit(ResourceCategory $category)
    {
        return view('admin.resource-categories.edit', [
            'category' => $category,
        ]);
    }

    public function update(ResourceCategoryRequest $request, ResourceCategory $category)
    {
        $category->update($this->validatedData($request, $category));

        return redirect()
            ->route('admin.resource-categories.index')
            ->with('success', 'Kategorien ble oppdatert.');
    }

    public function destroy(ResourceCategory $category)
    {
        if ($category->professionalResources()->exists()) {
            return redirect()
                ->route('admin.resource-categories.index')
                ->with('warning', 'Kategorien kan ikke slettes fordi den inneholder fagressurser.');
        }

        $category->delete();

        return redirect()
            ->route('admin.resource-categories.index')
            ->with('success', 'Kategorien ble slettet.');
    }

    private function validatedData(ResourceCategoryRequest $request, ?ResourceCategory $category = null): array
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['slug'] = $this->uniqueSlug($data['name'], $category);

        return $data;
    }

    private function uniqueSlug(string $name, ?ResourceCategory $category = null): string
    {
        $baseSlug = Str::slug($name) ?: 'kategori';
        $slug = $baseSlug;
        $suffix = 2;

        while (ResourceCategory::where('slug', $slug)
            ->when($category && $category->exists, function ($query) use ($category) {
                $query->where('id', '!=', $category->id);
            })
            ->exists()) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }
}
