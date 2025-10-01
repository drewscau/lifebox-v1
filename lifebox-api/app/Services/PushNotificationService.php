<?php

namespace App\Services;

use Symfony\Component\Console\Output\ConsoleOutput;

class PushNotificationService
{
    public static function sendPushNotification($request, $FcmToken, $extraData = null)
    {
        $out = new ConsoleOutput();
        $url = config('app.fcm_server_url');
        $serverKey = config('app.fcm_server_key');

        $notification = [
            "title" => $request['title'],
            "body" => $request['body'],
        ];

        $data = [
            "registration_ids" => $FcmToken,
            "notification" => $notification,
            "data" => isset($extraData) ? array_merge($notification, $extraData) : $notification,
            "android" => [
                "collapse_key" => "collapse_key_1",
                "priority" => "high",
                "notification" => [
                    "channel_id" => "lifebox-notifications",
                ]
            ],
            "apns" => [
                "headers" => [
                    "apns-priority" => "5",
                ],
                "payload" => [
                    "aps" => [
                        "content-available" => 1
                    ]
                ]
            ],
        ];
        $encodedData = json_encode($data);

        $headers = [
            'Authorization:key=' . $serverKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);

        // Execute post
        $result = curl_exec($ch);

        $out->writeln("sendPushNotification result: " . print_r($result, true));

        if ($result === FALSE) {
            die('Curl failed: ' . curl_error($ch));
        }

        // Close connection
        curl_close($ch);

        // FCM response
        return $result;
    }
}
