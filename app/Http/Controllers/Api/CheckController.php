<?php

namespace App\Http\Controllers\Api;

use App\Models\Check;
use App\Http\Requests\CheckDepositRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Account;

class CheckController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function deposit(CheckDepositRequest $request)
    {
        $user = auth()?->user();

        abort_if(!$user, 403);

        $account = $user?->account ?? Account::create([ // TODO: validate if != Admin
            'user_id' => $user?->id,
            'balance' => 0,
        ]);

        return response()->json([
            // $request?->file('check_image')?->extension(),
            // $request?->file('check_image')?->getFilename(),
            // $request?->file('check_image')?->getPath(),
            // $request?->file('check_image')?->getRealPath(),
            // $request?->file('check_image')?->getClientMimeType(),
            // $request?->file('check_image')?->getClientOriginalName(),
            // $request?->file('check_image')?->getClientOriginalExtension(),
            // $request?->file('check_image')?->getSize(),

            'deposit' => [
                'title' => $request->input('title'),
                'amount' => $request->input('amount'),
                'success' => true,
                'status' => 30,
                'check_image_url' => url(''),
                'account' => $account?->id,
            ],
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Check $check)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Check $check)
    {
        //
    }
}
