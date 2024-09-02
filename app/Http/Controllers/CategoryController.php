<?php

namespace App\Http\Controllers;

use App\Models\Headline;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(['categories' => Headline::CATEGORIES]);
    }
}
