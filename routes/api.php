<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::group(['middleware' => 'api'], function () {
  Route::group(['prefix' => 'v1'], function () {
    Route::group(['prefix' => 'auth'], function () {
      Route::post('/sign_up', 'UsersController@store')->name('auth.sign_up');
      Route::post('/sign_in', 'UsersController@login')->name('auth.sign_in');
      Route::delete('/resign', 'UsersController@destroy')->name('auth.resign');
    });

    Route::group(['prefix' => 'users'], function () {
      Route::get('/', 'UsersController@index')->name('users.index');
      Route::get('/{id}', 'UsersController@show')->name('users.show');
      Route::put('/{id}', 'UsersController@update')->name('users.update');
      Route::delete('/{id}', 'UsersController@destroy')->name('users.destroy');
    });

    Route::resource('todos', 'TodosController', ['only' => ['index', 'store', 'show', 'update', 'destroy']]);
  });
});
