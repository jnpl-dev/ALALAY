<?php

namespace App\Http\Controllers\Treasurer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChequeController extends Controller
{
    public function index()
    {
        return inertia('Treasurer/Cheques/Index');
    }

    public function show($voucher)
    {
        return inertia('Treasurer/Cheques/Review');
    }

    public function acknowledge(Request $request, $voucher)
    {
        return redirect()->route('treasurer.cheques.index');
    }
}
