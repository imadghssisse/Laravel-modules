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


// Global prifix actions for all api actionsBoard

Route::group(['middleware' => 'auth:api'], function () {

    // api Tags and Actions
    Route::get('worlds/{world}/actionsboard/tags/{entity}/{type}', 'TagsController@index')->name('module.actionsboard.tag.index'); // Get all type tags
    Route::post('worlds/{world}/actionsboard/tags/{entity}/{type}', 'TagsController@store')->name('module.actionsboard.tag.store'); // Store tag
    Route::get('worlds/{world}/actionsboard/tribes', 'TagsController@tribes')->name('module.actionsboard.tribes.index'); // Get all tribis world
    Route::get('worlds/{world}/actionsboard/users', '\Modules\Actionsboard\Http\Controllers\ActionController@getUsers')->name('module.actionsboard.users'); // Get Users belonge to wolrd

    Route::get('worlds/{world}/actionsboard/Actions', 'ActionController@index')->name('module.actionsboard.actions.index');  // Get Actions User in world
    Route::post('worlds/{world}/actionsboard/Actions', 'ActionController@store')->name('module.actionsboard.actions.store'); // Store Action
    Route::put('worlds/{world}/actionsboard/Actions/{id}', 'ActionController@update')->name('module.actionsboard.actions.update'); // Update Action
    Route::put('worlds/{world}/actionsboard/Actions/date/{id}', 'ActionController@updateDate');
    Route::get('worlds/{world}/actionsboard/reccurence/{entity}', 'ActionController@recurrenceAction');
    Route::get('worlds/{world}/actionsboard/histiriq/{entity}', 'ActionController@histiriq');
    Route::delete('worlds/{world}/actionsboard/Actions/{id}', 'ActionController@destroy')->name('module.actionsboard.actions.destroy'); // Delete Action
    Route::post('worlds/{world}/actionsboard/Actions/comment', 'ActionController@storeComment')->name('module.actionsboard.actions.comment.store'); // Add comment for action by user
    Route::post('worlds/{world}/actionsboard/{action}/activated-action', 'ActionController@ownerActivated')->name('module.actionsboard.actions.assign.status'); // Change Status action for user owner
    Route::get('worlds/{world}/actionsboard/assign/{entity}', 'ActionController@actionAssign')->name('module.actionsboard.actions.assign'); //Get actions assigne to user
    Route::put('worlds/{world}/actionsboard/tag/{entity}/{id}', 'ActionController@updateTag')->name('module.actionsboard.actions.tag.update'); // update tag for action

    // Fast
    Route::get('worlds/{world}/actionsboard/fast', 'FastController@index');
    Route::post('worlds/{world}/actionsboard/fast', 'FastController@store');
    Route::delete('worlds/{world}/actionsboard/fast/{id}', 'FastController@destroy');


});

Route::post('webhook-action/create', 'WebHookActionController@storeAction')->name('module.actionsboard.web-hook'); // Web hook api for store action
