<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Requests\UpdateCheckRequest;
use App\Models\Check;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminCheckController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Check $check)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCheckRequest $request, Check $check)
    {
        //
    }
}
