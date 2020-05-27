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



Route::prefix('v1')->namespace('Api\V1')->name('api.v1.')->group(function () {

    Route::get('ping', 'TestController@ping');
    Route::any('test', 'TestController@test');


    Route::post('user/register', 'UserController@register');
    Route::post('user/send-sms-code', 'UserController@sendSmsCode');
    Route::post('user/login', 'UserController@login');
    Route::post('user/change-password', 'UserController@changePassword');

    Route::middleware('auth:api_user')->group(function() {
        // 当前登录用户信息
        Route::get('user', 'TestController@me');
        Route::post('intelligent-creation/upload-user-resource', 'IntelligentCreationController@uploadUserResource');
        Route::post('intelligent-creation/upload-bg-music', 'IntelligentCreationController@uploadBgMusic');
        Route::get('intelligent-creation/user-resource-list', 'IntelligentCreationController@userResourceList');
        Route::get('intelligent-creation/list-of-options', 'IntelligentCreationController@listOfOptions');
        Route::post('intelligent-creation/create-timeline-task', 'IntelligentCreationController@create_timeline_task');
    });


    Route::post('intelligent-creation/analysis-baijiahao-article-by-url', 'IntelligentCreationController@analysisBaiJiaHaoArticleByUrl');
    Route::get('intelligent-creation/video-search', 'IntelligentCreationController@getSearch');
    Route::get('intelligent-creation/video-search-person', 'IntelligentCreationController@getSearchPerson');
    Route::get('intelligent-creation/video-search-object', 'IntelligentCreationController@getSearchObject');
    Route::post('intelligent-creation/cut-video-done', 'IntelligentCreationController@cutVideoDone');




});
