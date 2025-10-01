<?php

namespace App\Serializers;

use Illuminate\Notifications\DatabaseNotification;

class NotificationSerializer extends Serializer
{
    /**
     * Array data structure output of model
     *
     * @param Illuminate\Notifications\DatabaseNotification $model
     * @return array
     */
    protected function serialize(DatabaseNotification $model) : array
    {
        $template = view($model->data['blade'], [
            'notification' => $model
        ]);

        return [
            'id' => $model->id,
            'unread' => $model->unread(),
            'archived' => $model->archived(),
            'router' => $model->data['router'],
            'message' => $template->render(),
        ];
    }
}
