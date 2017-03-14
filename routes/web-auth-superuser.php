<?php

/*
|--------------------------------------------------------------------------
| Web Routes - These Required an Auth'd User
|--------------------------------------------------------------------------
*/


Route::group( [ 'namespace' => 'PatchPanel', 'prefix' => 'patch-panel' ], function() {
    Route::get( 'list',                        'PatchPanelController@index' );
    Route::get( 'list/inactive',               'PatchPanelController@indexInactive' );

    Route::get( 'add',                         'PatchPanelController@edit'   );
    Route::get( 'edit/{id}',                   'PatchPanelController@edit'   );
    Route::get( 'view/{id}',                   'PatchPanelController@view'   );
    Route::get( 'change-status/{id}/{active}', 'PatchPanelController@changeStatus' );

    Route::post( 'store',                     'PatchPanelController@store'  );
});

Route::group( [ 'namespace' => 'PatchPanel', 'prefix' => 'patch-panel-port' ], function() {
    Route::get( 'list',                         'PatchPanelPortController@index' )->name('patchPanelPortIndex');
    Route::get( 'list/patch-panel/{id}',        'PatchPanelPortController@index' );

    Route::get( 'edit/{id}/{allocating?}',      'PatchPanelPortController@edit' );
    Route::get( 'getSwitchPort',                'PatchPanelPortController@getSwitchPort' );
    Route::get( 'getCustomerForASwitchPort',    'PatchPanelPortController@getCustomerForASwitchPort' );
    Route::get( 'getSwitchForACustomer',        'PatchPanelPortController@getSwitchForACustomer' );
    Route::get( 'checkPhysicalInterfaceMatch',  'PatchPanelPortController@checkPhysicalInterfaceMatch' );
    Route::get( 'changeStatus/{id}/{status}',   'PatchPanelPortController@changeStatus' );
    Route::get( 'email/{id}/{type}',            'PatchPanelPortController@email' );
    Route::get( 'setNotes',                     'PatchPanelPortController@setNotes' );
    Route::get( 'history/{id}',                 'PatchPanelPortController@history' );
    Route::get( 'downloadFile/{id}',            'PatchPanelPortController@downloadFile' );

    Route::post( 'uploadFile/{id}',             'PatchPanelPortController@uploadFile' );

    Route::get(  'deleteFile',                  'PatchPanelPortController@deleteFile' );
    Route::get(  'changePrivateFile',           'PatchPanelPortController@changePrivateFile' );

    Route::post( 'store',                       'PatchPanelPortController@store' );
    Route::post( 'sendEmail',                   'PatchPanelPortController@sendEmail' );

});
