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
// Global prifix marketplace for all api MarketPlace
Route::group(['middleware' => 'auth:api'], function() {
    // api MarketPlace
    Route::get('worlds/{world}/marketplace/{entity}/list-categories', 'ProductController@getListTags')->name('module.marketplace.list_tags');// get list of tags
    Route::post('worlds/{world}/marketplace/users', 'ProductController@users')->name('module.marketplace.users'); // get list users in world
    Route::get('worlds/{world}/marketplace/{entity}', 'ProductController@index')->name('module.marketplace.product.index'); // get list product in world
    Route::post('worlds/{world}/marketplace/{entity}/store', 'ProductController@store')->name('module.marketplace.product.store'); // store product
    Route::post('worlds/{world}/marketplace/{entity}/{produit}/update', 'ProductController@update')->name('module.marketplace.product.update'); //update product
    Route::post('worlds/{world}/marketplace/{entity}/delete', 'ProductController@delete')->name('module.marketplace.product.delete'); // dlete product
    Route::post('worlds/{world}/marketplace/{entity}/set/tag', 'ProductController@setTag')->name('module.marketplace.product.settag'); //set new tag
    Route::post('worlds/{world}/marketplace/{entity}/wishlist', 'ProductController@wishlist')->name('module.marketplace.wishlist'); // add or remove wishlist
    Route::post('worlds/{world}/marketplace/{entity}/comment', 'CommentsController@set')->name('module.marketplace.comment'); //add new commantaire
    Route::post('worlds/{world}/marketplace/{entity}/command/store', 'CommandController@store')->name('module.marketplace.command.store'); //add new command or delete
    Route::get('worlds/{world}/marketplace/{entity}/command/list', 'CommandController@list')->name('module.marketplace.command.index'); // get all commands for user

});


Route::post('webhook-product/create', 'WebHookProductController@store')->name('module.marketplace.web-hook.store'); // store product by web-hook
Route::post('webhook-product/update', 'WebHookProductController@update')->name('module.marketplace.web-hook.update'); // updat product by web-hook
Route::post('webhook-product/delete', 'WebHookProductController@delete')->name('module.marketplace.web-hook.delete');// delete product by web-hook
