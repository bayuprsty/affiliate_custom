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
Route::post('/getAccessToken', 'Api\ApiController@getAccessToken');

Route::group(['middleware' => 'auth.api'], function() {
    Route::post('/setLeadData', 'Api\ApiController@setDataLead')->name('api.lead');
    Route::post('/setTransactionData', 'Api\ApiController@setDataTransaction')->name('api.transaction');
});
