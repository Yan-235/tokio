<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['middleware' => ['auth']], function () {
	// Uses first & second Middleware
	Route::get('/', 'TokioController@index')->name('main');
	Route::get('/info-tables', 'TokioController@showInfoTables')->name('info-tables');
	Route::get('/master/{id}', 'TokioController@masterService')->name('master-service');
	Route::get('/delete/{id}', 'TokioController@deleteUser')->name('delete-user');
	Route::get('/add-master', 'TokioController@addMaster')->name('add-master');
	Route::get('/update-master', 'TokioController@updateMaster')->name('update-master');
	Route::get('/add-service', 'TokioController@addService')->name('add-service');
	Route::get('/add-service-to-salon', 'TokioController@addServiceToSalon')->name('add-service-to-salon');
	Route::get('/add-good-to-salon', 'TokioController@addGoodToSalon')->name('add-good-to-salon');
	Route::get('/delete-service', 'TokioController@deleteService')->name('delete-service');
	Route::get('/delete-sale', 'TokioController@deleteSale')->name('delete-sale');
	Route::get('/client-delete-service', 'TokioController@clientDeleteService')->name('client-delete-service');
	Route::get('/client-delete-sale', 'TokioController@clientDeleteSale')->name('client-delete-sale');
	Route::get('/tokio-logout', 'TokioController@logout')->name('tokio-logout');
	Route::get('/change-salon', 'TokioController@changeSalon')->name('change-salon');
	Route::get('/date-filter', 'TokioController@dateFilter')->name('date-filter');
	Route::get('/shift-date-filter', 'TokioController@shiftDateFilter')->name('shift-date-filter');
	Route::get('/shifts', 'TokioController@showAddShift')->name('show-add-shift');
	Route::get('/add-shift', 'TokioController@addShift')->name('add-shift');
	Route::get('/add-cost', 'TokioController@addCost')->name('add-cost');
	Route::get('/add-discount', 'TokioController@addDiscount')->name('add-discount');
	Route::get('/add-text', 'TokioController@addText')->name('add-text');
	Route::get('/add-cost-sale', 'TokioController@addCostSale')->name('add-cost-sale');
	Route::get('/add-discount-sale', 'TokioController@addDiscountSale')->name('add-discount-sale');
	Route::get('/add-text-sale', 'TokioController@addTextSale')->name('add-text-sale');
	Route::get('/client-add-cost', 'TokioController@clientAddCost')->name('client-add-cost');
	Route::get('/goods-client-add-cost', 'TokioController@goodsclientAddCost')->name('goods-client-add-cost');
	Route::get('/client-add-text', 'TokioController@clientAddText')->name('client-add-text');
	Route::get('/goods-client-add-text', 'TokioController@goodsClientAddText')->name('goods-client-add-text');
	Route::get('/client-add-discount', 'TokioController@clientAddDiscount')->name('client-add-discount');
	Route::get('/goods-client-add-discount', 'TokioController@goodsClientAddDiscount')->name('goods-client-add-discount');
	Route::get('/show-client-list', 'TokioController@showClientList')->name('show-client-list');
	Route::get('/client/{id}', 'TokioController@showClient')->name('show-client');
	Route::get('/goods-client/{id}', 'TokioController@goodsShowClient')->name('goods-show-client');
	Route::get('/delete-client/{id}', 'TokioController@deleteClient')->name('delete-client');
	Route::get('/update-client', 'TokioController@updateClient')->name('update-client');
	Route::get('/add-client', 'TokioController@addClient')->name('add-client');
	Route::get('/client-date-filter', 'TokioController@clientDateFilter')->name('client-date-filter');
	Route::get('/client-add-service', 'TokioController@clientAddService')->name('client-add-service');
	Route::get('/choose-master', 'TokioController@chooseMaster')->name('choose-master');
	Route::get('/goods-choose-master', 'TokioController@goodsChooseMaster')->name('goods-choose-master');
	Route::get('/choose-date', 'TokioController@chooseDate')->name('choose-date');
	Route::get('/add-sale', 'TokioController@addSale')->name('add-sale');
	Route::get('/client-add-sale', 'TokioController@clientAddSale')->name('client-add-sale');
	Route::get('/current-total-count', 'TokioController@currentTotalCount')->name('current-total-count');
	Route::get('/master-feedback', 'TokioController@masterFeedback')->name('master-feedback');
	Route::get('/show-day-report', 'TokioController@showDayReport')->name('show-day-report');
	Route::get('/add-report', 'TokioController@addReport')->name('add-report');
	Route::get('/delete-product/{id}', 'TokioController@deleteProduct')->name('delete-product');
	Route::get('/delete-good/{id}', 'TokioController@deleteGood')->name('delete-good');
	Route::get('/update-feedbacks', 'TokioController@updateFeedbacks')->name('update-feedbacks');

	Route::get('/search-client', 'SearchController@searchClient')->name('search-client');

	Route::get('/live-search','TokioController@liveSearch')->name('live-search');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

