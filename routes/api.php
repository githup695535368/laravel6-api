<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/



Route::prefix('v1')->namespace('Api\V1')->name('api.v1.')->middleware(['cors'])->group(function () {
    // 短信验证码
    Route::get('ping', 'TestController@ping');
    Route::any('test', 'TestController@test');


    Route::middleware('auth:api_user')->group(function() {
        // 当前登录用户信息
        Route::get('user', 'TestController@me');
    });


    Route::prefix('inte')->group(function () {
        Route::get('users', function () {
            // 匹配包含 「/admin/users」 的 URL
        });
    });


    Route::any('intelligent-creation/crawl-img-text-map-by-baijiahao-url', 'IntelligentCreationController@AnalysisBaiJiaHaoArticleByUrl');



});
