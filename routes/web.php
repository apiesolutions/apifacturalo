<?php

    Auth::routes();

    Route::get('downloads/document/{type}/{external_id}', 'DocumentController@downloadExternal')->name('documents.download_external');
    Route::get('downloads/summary/{type}/{external_id}', 'SummaryController@downloadExternal')->name('summaries.download_external');
    Route::get('downloads/voided/{type}/{external_id}', 'VoidedController@downloadExternal')->name('voided.download_external');

    Route::middleware('auth')->group(function() {

        Route::get('/', function () {
            if(auth()->user()->role === 'user') {
                return redirect()->route('documents.index');
            } else {
                return redirect()->route('users.index');
            }
        });
        Route::get('dashboard', 'HomeController@index')->name('dashboard');

        //Company
        Route::get('companies/create', 'CompanyController@create')->name('companies.create');
        Route::get('companies/tables', 'CompanyController@tables');
        Route::get('companies/record', 'CompanyController@record');
        Route::post('companies', 'CompanyController@store');
        Route::post('companies/uploads', 'CompanyController@uploadFile');

        //Certificates
        Route::get('certificates/record', 'CertificateController@record');
        Route::post('certificates/uploads', 'CertificateController@uploadFile');
        Route::delete('certificates', 'CertificateController@destroy');

        //Users
        Route::get('users', 'UserController@index')->name('users.index');
        Route::get('users/columns', 'UserController@columns');
        Route::get('users/tables', 'UserController@tables');
        Route::get('users/record/{user}', 'UserController@record');
        Route::post('users', 'UserController@store');
        Route::get('users/records', 'UserController@records');
        Route::delete('users/{user}', 'UserController@destroy');

        //Documents
        Route::get('documents', 'DocumentController@index')->name('documents.index');
        Route::get('documents/columns', 'DocumentController@columns');
        Route::get('documents/records', 'DocumentController@records');

        Route::post('options/delete_documents', 'OptionController@deleteDocuments');

        Route::get('services/ruc/{number}', 'Api\ServiceController@ruc');
        Route::get('services/dni/{number}', 'Api\ServiceController@dni');
    });
