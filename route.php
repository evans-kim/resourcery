<?php

use EvansKim\Resourcery\Controller\UserController;
use EvansKim\Resourcery\Controller\ResourceActionController;
use EvansKim\Resourcery\Controller\ResourceManagerController;
use EvansKim\Resourcery\Controller\RoleController;
use EvansKim\Resourcery\ResourceRouter;
use Illuminate\Support\Facades\Route;

Route::prefix(config('resourcery.base_uri'))->group(function(){
    Route::apiResource('/resource-manager', ResourceManagerController::class);
    Route::apiResource('/resource-action', ResourceActionController::class);
    Route::apiResource('/user', UserController::class);
    Route::apiResource('/role', RoleController::class);
});

// 라우트를 덤프 하면
if(file_exists(config('resourcery.cache_path'))){
    Route::name('resourcery.')->group(config('resourcery.cache_path'));
}else{
    Route::name('resourcery.')->group(
        function (){
            ResourceRouter::createRoutes();
        }
    );
}



