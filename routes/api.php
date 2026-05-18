<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'node' => config('distributed.node.id'),
    ]);
});

Route::post('/sync', function (Request $request) {
    return response()->json([
        'success' => true,
        'message' => 'Sync received',
        'node_id' => config('distributed.node.id'),
        'payload' => $request->all(),
    ]);
});
