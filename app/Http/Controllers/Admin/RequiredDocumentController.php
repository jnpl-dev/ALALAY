<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssistanceCategory;
use App\Models\RequiredDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class RequiredDocumentController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', RequiredDocument::class);
        $search = request('search');
        $category_id = request('category_id');

        $documents = RequiredDocument::with('category')
            ->when($search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('doc_name', 'like', "%{$s}%")
                  ->orWhere('doc_description', 'like', "%{$s}%");
            }))
            ->when($category_id, fn ($q, $c) => $q->where('category_id', $c))
            ->latest()
            ->paginate(10)
            ->through(fn ($doc) => [
                'id' => $doc->id,
                'doc_name' => $doc->doc_name,
                'doc_description' => $doc->doc_description,
                'is_mandatory' => $doc->is_mandatory,
                'is_active' => $doc->is_active,
                'category_id' => $doc->category_id,
                'category_name' => $doc->category?->category_name,
                'created_at' => $doc->created_at,
            ]);

        $categories = AssistanceCategory::orderBy('category_name')
            ->get(['id', 'category_name']);

        return Inertia::render('Admin/RequiredDocuments/Index', [
            'documents' => Inertia::defer(fn () => $documents),
            'filters' => request()->only(['search', 'category_id']),
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        $this->authorize('create', RequiredDocument::class);
        $categories = AssistanceCategory::orderBy('category_name')
            ->get(['id', 'category_name']);

        return Inertia::render('Admin/RequiredDocuments/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', RequiredDocument::class);
        $validated = $request->validate([
            'category_id' => ['required', 'exists:assistance_categories,id'],
            'doc_name' => ['required', 'string', 'max:200'],
            'doc_description' => ['nullable', 'string'],
            'is_mandatory' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        RequiredDocument::create($validated);
        Cache::forget('categories.with_docs');

        return redirect()->route('admin.required-documents.index')
            ->with('success', 'Required document created successfully.');
    }

    public function edit($id)
    {
        $this->authorize('update', RequiredDocument::class);
        $doc = RequiredDocument::findOrFail($id);

        $categories = AssistanceCategory::orderBy('category_name')
            ->get(['id', 'category_name']);

        return Inertia::render('Admin/RequiredDocuments/Edit', [
            'document' => [
                'id' => $doc->id,
                'category_id' => $doc->category_id,
                'doc_name' => $doc->doc_name,
                'doc_description' => $doc->doc_description,
                'is_mandatory' => $doc->is_mandatory,
                'is_active' => $doc->is_active,
            ],
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('update', RequiredDocument::class);
        $doc = RequiredDocument::findOrFail($id);

        $validated = $request->validate([
            'category_id' => ['required', 'exists:assistance_categories,id'],
            'doc_name' => ['required', 'string', 'max:200'],
            'doc_description' => ['nullable', 'string'],
            'is_mandatory' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $doc->update($validated);
        Cache::forget('categories.with_docs');

        return redirect()->route('admin.required-documents.index')
            ->with('success', 'Required document updated successfully.');
    }

    public function destroy($id)
    {
        $this->authorize('delete', RequiredDocument::class);
        $doc = RequiredDocument::findOrFail($id);
        $doc->delete();
        Cache::forget('categories.with_docs');

        return redirect()->route('admin.required-documents.index')
            ->with('success', 'Required document deleted successfully.');
    }
}
