<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppFile;

class AppFileController extends Controller
{
    public function show(Request $request, string|int $appFile)
    {
        $isAdmin = $request->boolean('isAdmin', false); // TODO: change to use policy

        $appFile = AppFile::when(
            !$isAdmin,
            fn ($query) => $query->forUser()
        )
        ->where('id', $appFile)
        ->firstOrFail();

        $appFilePath = $appFile?->getStoragePath();

        abort_if(!$appFilePath, 404);

        return response()->file($appFilePath);
    }
}
