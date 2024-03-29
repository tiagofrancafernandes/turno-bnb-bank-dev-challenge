<?php

namespace App\Http\Controllers\Api;

use App\Models\Check;
use App\Http\Requests\CheckDepositRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppFile;
use Illuminate\Database\Eloquent\Builder;

class CheckController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()?->user();

        abort_if(!$user, 403);

        $isAdmin = $request->boolean('isAdmin', false); // TODO: change to use policy

        $query = Check::latest('id')
            ->with('appFile')
            ->when(
                !$isAdmin,
                fn (Builder $query) => $query->forUser()
            )
            ->when(
                $request->input('status'),
                fn (Builder $query, $status) => $query->byStatus($status)
            );

        return response()->json(
            $query?->paginate(20),
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function deposit(CheckDepositRequest $request)
    {
        $user = auth()?->user();

        abort_if(!$user, 403);

        $account = $user?->getAccountOrCreate(0); // TODO: validate if != Admin

        $preparedFile = AppFile::prepareFile(
            sourcePath: $request?->file('check_image')?->getRealPath(),
            diskName: 'public',
            dirToSave: implode('/', ['check-images', 'user-' . $user?->id]),
            prefix: date('Y-m-d-'),
            originalName: $request?->file('check_image')?->getClientOriginalName(),
        );

        $appFile = $preparedFile ? AppFile::create([
            'path' => $preparedFile?->finalPath,
            'original_name' => $preparedFile?->originalName ?: $request?->file('check_image')?->getClientOriginalName(),
            'disk' => $preparedFile?->diskName ?: 'public',
            'public' => false,
            'user_id' => $user?->id,
        ]) : null;

        if (!$appFile) {
            return response()->json([
                'message' => __('Fail on upload check image'),
            ], 422);
        }

        $check = Check::create([
            'title' => $request->input('title'),
            'amount' => $request->input('amount'),
            'status' => \App\Enums\CheckStatus::WAITING,
            'check_image_file_id' => $appFile?->id,
            'account_id' => $account?->id,
        ]);

        return response()->json([
            'deposit' => $check->only([
                'title',
                'amount',
                'status',
                'checkImageUrl',
                'account_id',
            ]),
            'success' => true,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string|int $check)
    {
        $isAdmin = $request->boolean('isAdmin', false); // TODO: change to use policy

        $query = Check::when(
            !$isAdmin,
            fn (Builder $query) => $query->forUser()
        );

        $check = $query->where('id', $check)->firstOrFail();

        $appFilePath = $check?->getStoragePath();

        abort_if(!$appFilePath, 404);

        return response()->file($appFilePath);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Check $check)
    {
        //
    }
}
