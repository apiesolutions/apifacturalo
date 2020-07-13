<?php

Route::middleware('auth:api')->group(function() {
    Route::post('documents', 'Api\DocumentController@store');
    Route::post('send', 'Api\DocumentController@send');
    Route::post('summaries/status', 'Api\SummaryController@status');
    Route::post('summaries', 'Api\SummaryController@store');
    Route::post('voided/status', 'Api\VoidedController@status');
    Route::post('voided', 'Api\VoidedController@store');
    Route::get('services/ruc/{number}', 'Api\ServiceController@ruc');
    Route::get('services/dni/{number}', 'Api\ServiceController@dni');
});
Route::post('services/validate_cpe', 'Api\ServiceController@validateCpe');
Route::post('services/consult_status', 'Api\ServiceController@consultStatus');
Route::post('services/consult_cdr_status', 'Api\ServiceController@consultCdrStatus');
Route::post('services/search_document', 'Api\DocumentController@getLinks');
