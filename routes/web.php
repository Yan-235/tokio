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

Route::group(['middleware'=>['auth']], function () {
        // Uses first & second Middleware
    Route::get('/', 'TokioController@index')->name('main');
    Route::get('/master/{id}', 'TokioController@masterSale')->name('master-sale');
    Route::get('/delete/{id}', 'TokioController@deleteUser')->name('delete-user');
    Route::get('/add-master', 'TokioController@addMaster')->name('add-master');
    Route::get('/update-master', 'TokioController@updateMaster')->name('update-master');
    Route::get('/add-service', 'TokioController@addService')->name('add-service');
    Route::get('/add-sale', 'TokioController@addSale')->name('add-sale');
    Route::get('/delete-sale', 'TokioController@deleteSale')->name('delete-sale');
    Route::get('/tokio-logout', 'TokioController@logout')->name('tokio-logout');
    Route::get('/change-salon', 'TokioController@changeSalon')->name('change-salon');
    Route::get('/date-filter', 'TokioController@dateFilter')->name('date-filter');
    Route::get('/shift-date-filter', 'TokioController@shiftDateFilter')->name('shift-date-filter');
    Route::get('/shifts', 'TokioController@showAddShift')->name('show-add-shift');
    Route::get('/add-shift', 'TokioController@addShift')->name('add-shift');
});


Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

