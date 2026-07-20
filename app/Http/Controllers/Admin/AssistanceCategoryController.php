<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssistanceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AssistanceCategoryController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', AssistanceCategory::class);
        $search = request('search');

        $categories = AssistanceCategory::query()
            ->when($search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('category_name', 'like', "%{$s}%")
                  ->orWhere('category_description', 'like', "%{$s}%");
            }))
            ->latest()
            ->paginate(10)
            ->through(fn ($cat) => [
                'id' => $cat->id,
                'category_name' => $cat->category_name,
                'category_description' => $cat->category_description,
                'is_active' => $cat->is_active,
                'documents_count' => $cat->requiredDocuments()->count(),
                'created_at' => $cat->created_at,
            ]);

        return Inertia::render('Admin/AssistanceCategories/Index', [
            'categories' => Inertia::defer(fn () => $categories),
            'filters' => request()->only(['search']),
        ]);
    }

    public function create()
    {
        $this->authorize('create', AssistanceCategory::class);
        return Inertia::render('Admin/AssistanceCategories/Create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', AssistanceCategory::class);
        $validated = $request->validate([
            'category_name' => ['required', 'string', 'max:150', 'unique:assistance_categories,category_name'],
            'category_description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        AssistanceCategory::create($validated);
        Cache::forget('categories.with_docs');
        Cache::forget('categories.active_names');

        return redirect()->route('admin.assistance-categories.index')
            ->with('success', 'Assistance category created successfully.');
    }

    public function edit($id)
    {
        $this->authorize('update', AssistanceCategory::class);
        $category = AssistanceCategory::findOrFail($id);

        return Inertia::render('Admin/AssistanceCategories/Edit', [
            'category' => [
                'id' => $category->id,
                'category_name' => $category->category_name,
                'category_description' => $category->category_description,
                'is_active' => $category->is_active,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('update', AssistanceCategory::class);
        $category = AssistanceCategory::findOrFail($id);

        $validated = $request->validate([
            'category_name' => ['required', 'string', 'max:150', 'unique:assistance_categories,category_name,' . $id],
            'category_description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $category->update($validated);
        Cache::forget('categories.with_docs');
        Cache::forget('categories.active_names');

        return redirect()->route('admin.assistance-categories.index')
            ->with('success', 'Assistance category updated successfully.');
    }

    public function destroy($id)
    {
        $this->authorize('delete', AssistanceCategory::class);
        $category = AssistanceCategory::findOrFail($id);
        $category->delete();
        Cache::forget('categories.with_docs');
        Cache::forget('categories.active_names');

        return redirect()->route('admin.assistance-categories.index')
            ->with('success', 'Assistance category deleted successfully.');
    }
}
