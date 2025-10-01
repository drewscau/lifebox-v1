<?php

require_once __DIR__ . '/api.php';

// add tradebox only routes

Route::get('/ping', function () {
    return ['pong'];
});

// overwrite in api.php
Route::get('/email/verify/{id}', 'VerificationController@verify')->name('tradebox.verification.verify');
