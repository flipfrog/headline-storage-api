<?php

use App\Http\Controllers\HeadlineController;
use Illuminate\Support\Facades\Route;

Route::resource('headlines', HeadlineController::class)->except(['create', 'edit']);
