<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\TagTypeController;
use Illuminate\Support\Facades\Route;

// Route::get('/', 'PingController');
Route::post('/login', 'LoginController');
Route::post('/register', 'RegisterController');
Route::get('/email/verify/{id}', 'VerificationController@verify')->name('verification.verify');
Route::post('/forgot', 'ForgotPasswordController');
Route::post('reset-password/{token}', 'ResetPasswordController');
Route::get('/email/resend', 'VerificationController@resend')->name('verification.resend');
Route::get('/files/{file_id}/download{ext?}', 'FileController@download');

Route::get('/accounts/{user}/mails/unsubscribe', [AccountController::class, 'mailUnsubscribe'])->name('mail.unsubscribe');
Route::get('/accounts/{user}/terminate', [AccountController::class, 'terminate'])->name('account.terminate');
Route::get('/accounts/{user}/files/download-all', [AccountController::class, 'downloadAll'])->name('files.downloadAll');
Route::get('/coupon/{couponId}', 'CouponController@getCoupon');

Route::middleware(['auth:api', 'activated', 'scopes:' . config('passport.route_scope', 'lifebox')])
    ->group(function () {
        Route::post('change-password', 'ChangePasswordController');
        Route::get('/me', 'MeController@show');
        Route::patch('/me', 'MeController@update');
        Route::post('/me', 'MeController@update');
        Route::get('/me/check-subscriptions', 'MeController@checkSubscriptions');
        Route::get('/me/files', 'MeController@getUserFileDetail');
        Route::delete('/me/photo', 'MeController@removePhoto');

        Route::post('/me/push-token', 'PushNotificationController@storeToken');

        Route::post('/users/account', 'UserAdminController@getByAccount');
        Route::apiResource('/users', 'UserAdminController', [
            'except' => ['destroy', 'index']
        ]);
        Route::apiResource('/tags', 'TagController');
        Route::get('/tag-types', [TagTypeController::class, 'index']);

        Route::post('/files/create-file', 'FileController@createFileEntry');

        Route::middleware('subscribed')
            ->group(function () {
                Route::delete('/files/clear-trash', 'FileController@clearTrash');
                Route::post('/files/create-folder', 'FileController@createFolder');
                Route::patch('/files/{file_id}/restore', 'FileController@restore');
                Route::post('/files/{file_id}/trash', 'FileController@trash');
                Route::post('/files/{file_id}/copy', 'FileController@copy');
                Route::get('/files/reminder-notifications', 'FileReminderController@getNotifications');
                Route::apiResource('/reminders', 'ReminderController');
                Route::apiResource('/files/{file_id}/tags', 'FileTagController');
                Route::apiResource('/files/{file_id}/tags/{tag_id}/properties', 'FileTagPropController');
                Route::apiResource('/files/{file_id}/reminders', 'FileReminderController');
                Route::apiResource('/files', 'FileController');

                Route::get('/home', 'FileCustomController@home');
                Route::get('/trash', 'FileCustomController@trash');
                Route::get('/move', 'FileCustomController@move');
                Route::post('/get-contents', 'FileCustomController@getContents');
                Route::post('/create-folder', 'FileCustomController@createFolder');
                Route::get('/open-folder', 'FileCustomController@openFolder');
                Route::get('/copy-paste', 'FileCustomController@copyAndPaste');
                Route::get('/get-files', 'FileCustomController@getUserFiles');
                Route::get('/download-file', 'FileCustomController@downloadFile');
                Route::post('/upload-file', 'FileCustomController@store');
                Route::post('/tag-file', 'FileCustomController@tagFile');
                Route::get('/tags-on-file', 'FileCustomController@tagsOnFile');
                Route::get('/recent', 'FileCustomController@recent');
                Route::get('/reminder-notifications', 'FileCustomController@reminderNotifications');
                Route::get('/reminders-on-file', 'FileCustomController@remindersOnFile');
                Route::post('/add-file-reminder', 'FileCustomController@addFileReminder');
                Route::post('/create-tag', 'FileCustomController@createTag');
            });

        Route::post('/apply-coupon', 'PaymentController@applyCoupon');
        Route::get('/subscribe', 'PaymentController@showSubscription');
        Route::post('/subscribe', 'PaymentController@processSubscription');
        Route::patch('/subscribe', 'PaymentController@updateSubscription');
        Route::post('/delete-card', 'PaymentController@deleteCard');
        Route::get('/unsubscribe', 'PaymentController@unsubscribe');

        Route::post('/send-push-notification', 'PushNotificationController@sendWebPushNotification');

        Route::post('/subscription/in-app/validate', 'PaymentController@inAppValidation');
        Route::post('/subscription/in-app/store', 'PaymentController@inAppStore');
        Route::patch('/subscription/in-app/store', 'PaymentController@updateInAppStore');
        Route::post('/subscription/in-app/cancel', 'PaymentController@cancelInAppSubscription');
        Route::post('/subscription/in-app/refund', 'PaymentController@refundInAppSubscription');
        Route::post('/subscription/in-app/revoke', 'PaymentController@revokeInAppSubscription');

        Route::middleware('admin')
            ->group(function () {
                Route::apiResource('/users', 'UserAdminController');
                Route::apiResource('/retailer', 'RetailerController');
                Route::get('/user-activity/{user_id}', 'UserActivityController@show');
                Route::get('/user-activity', 'UserActivityController@index');
                Route::post('/send-notification/{user_id}', 'UserAdminController@sendPushNotificationToUsersDevice');
                Route::post('/voucher-code', 'VoucherCodeController@store');
                Route::get('/voucher-code', 'VoucherCodeController@list');
                Route::post('/coupon', 'CouponController@store');
                Route::get('/coupon', 'CouponController@list');
            });

        Route::middleware('subscribed_or_admin')
            ->group(function () {
                Route::post('/tag-property', 'TagPropertyController@store');
                Route::get('/tag-property/{tag_id}', 'TagPropertyController@getProperties');
                Route::delete('/tag-property/{tag_id}/{prop_id}', 'TagPropertyController@removeProperty');
                Route::put('/tag-property/{tag_id}/{prop_id}', 'TagPropertyController@update');
            });
        /**
         * V1 endpoints
         */
        Route::post('/logout', 'LogoutController');
        Route::get('/user', 'MeController@show');
    });
