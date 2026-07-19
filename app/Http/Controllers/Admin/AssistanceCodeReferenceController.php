<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssistanceCodeReference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AssistanceCodeReferenceController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', AssistanceCodeReference::class);
        $search = request('search');

        $references = AssistanceCodeReference::query()
            ->when($search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('code_type', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%");
            }))
            ->latest()
            ->paginate(10)
            ->through(fn ($ref) => [
                'id' => $ref->id,
                'code_type' => $ref->code_type,
                'default_amount' => $ref->default_amount,
                'description' => $ref->description,
                'is_active' => $ref->is_active,
                'created_at' => $ref->created_at,
            ]);

        return Inertia::render('Admin/AssistanceCodeReferences/Index', [
            'references' => $references,
            'filters' => request()->only(['search']),
        ]);
    }

    public function create()
    {
        $this->authorize('create', AssistanceCodeReference::class);
        return Inertia::render('Admin/AssistanceCodeReferences/Create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', AssistanceCodeReference::class);
        $validated = $request->validate([
            'code_type' => ['required', 'string', 'max:100', 'unique:assistance_code_references,code_type'],
            'default_amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        AssistanceCodeReference::create($validated);
        Cache::forget('code_references.active');

        return redirect()->route('admin.assistance-code-references.index')
            ->with('success', 'Code reference created successfully.');
    }

    public function edit($id)
    {
        $this->authorize('update', AssistanceCodeReference::class);
        $ref = AssistanceCodeReference::findOrFail($id);

        return Inertia::render('Admin/AssistanceCodeReferences/Edit', [
            'reference' => [
                'id' => $ref->id,
                'code_type' => $ref->code_type,
                'default_amount' => $ref->default_amount,
                'description' => $ref->description,
                'is_active' => $ref->is_active,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('update', AssistanceCodeReference::class);
        $ref = AssistanceCodeReference::findOrFail($id);

        $validated = $request->validate([
            'code_type' => ['required', 'string', 'max:100', 'unique:assistance_code_references,code_type,' . $id],
            'default_amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $ref->update($validated);
        Cache::forget('code_references.active');

        return redirect()->route('admin.assistance-code-references.index')
            ->with('success', 'Code reference updated successfully.');
    }

    public function destroy($id)
    {
        $this->authorize('delete', AssistanceCodeReference::class);
        $ref = AssistanceCodeReference::findOrFail($id);
        $ref->delete();
        Cache::forget('code_references.active');

        return redirect()->route('admin.assistance-code-references.index')
            ->with('success', 'Code reference deleted successfully.');
    }
}
