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

Route::group(['middleware' => ['auth:api','active']], function(){

    Route::apiResource('/union-bill-details','UnionBillDetailsController');

    Route::resource('/tax-collection','TaxCollectionController');
    Route::get('/tax/single-holding/{id}','TaxCollectionController@singleHoldingBill');
    Route::get('/years','TaxCollectionController@years');
    Route::get('/holding/unions','HoldingsController@unions');
    Route::get('/holding-types','HoldingsController@holdingType');
    Route::resource('/holdings','HoldingsController');
    Route::get('report/holdings','ReportsController@holdings');
    Route::get('report/prev-tax-collection','ReportsController@prevTaxCollection');
    Route::get('report/current-tax-collection','ReportsController@currentTaxCollection');
    Route::get('search','ReportsController@search');
    Route::get('report/daily-tax','ReportsController@dailyTax');
    Route::get('report/due-tax','ReportsController@dueReport');
    Route::get('report/all-holdings','ReportsController@autoSuggestions');
    Route::get('report/single-holding','ReportsController@singleHoldingBill');
    Route::get('union-top-sheet','TopSheetController@index');

    //Trade Licence
    Route::group(['middleware' => 'tradeLicence'], function () {
        Route::resource('/trade-licence', 'TradeLicenceController');
    });

    //permissions
    Route::get('user-permission','Api\UserController@userPermission');

    //sms
    Route::get('sms/holdings/{id}','SmsController@singleHoldingSms');

    Route::get('area/village/{id}','AreaController@villages');
    Route::get('area/bazar/{id}','AreaController@bazars');

    Route::group(['middleware' => 'admin'], function () {
        Route::get('user', 'Api\AuthController@getUser');
        Route::apiResource('/users','Api\UserController');
        Route::apiResource('/unions','UnionController');
        Route::apiResource('/village','VillageController');
        Route::apiResource('/bazar','BazarController');
        Route::post('users/password','Api\UserController@password');
        Route::get('area/districts/{id}','AreaController@districts');
        Route::get('area/upazila/{id}','AreaController@upazila');
        Route::get('area/unions/{id}','AreaController@unions');
        Route::get('area/divisions','AreaController@divisions');
    });
});

Route::get('mobile-no','TaxListController@index');


Route::post('login', 'Api\AuthController@login');
