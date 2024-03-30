<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppFile;
use Illuminate\Database\Eloquent\Builder;

class AppFileController extends Controller
{
    public function show(Request $request, string|int $appFile)
    {
        $user = $request->user();

        $appFile = AppFile::when(
            !$user?->isAdmin(),
            fn (Builder $query) => $query->forUser($user)
        )
        ->where('id', $appFile)
        ->firstOrFail();

        $appFilePath = $appFile?->getStoragePath();

        abort_if(!$appFilePath, 404);

        return response()->file($appFilePath);
    }
}
