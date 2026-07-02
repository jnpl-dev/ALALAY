<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AssistanceCategory;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = AssistanceCategory::active()
            ->with(['requiredDocuments' => fn($q) => $q->active()])
            ->orderBy('category_name')
            ->get();

        return Inertia::render('Public/Apply', [
            'categories' => $categories,
        ]);
    }
}
