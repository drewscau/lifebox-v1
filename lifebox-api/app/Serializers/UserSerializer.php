<?php

namespace App\Serializers;

use Auth;
use Storage;
use App\Models\User;

class UserSerializer extends Serializer
{
    /**
     * Array data structure output of model
     *
     * @param \App\Models\User $model
     * @return array
     */
    protected function serialize(User $model) : array
    {
        $dateFormat = 'l, F d, Y - g:i A';

        $attributes = [
            'id'                        => $model->id,
            'first_name'                => $model->first_name,
            'last_name'                 => $model->last_name,
            'mobile'                    => $model->mobile,
            'email'                     => $model->email,
            'account_number'            => $model->account_number,
            'username'                  => $model->username,
            'lifebox_email'             => $model->lifebox_email,
            'user_type'                 => $model->user_type,
            'user_status'               => $model->user_status,
            'is_admin'                  => $model->isAdmin(),
            'stripe_customer_id'        => $model->stripe_id,
            'created_at'                => date($dateFormat, strtotime($model->created_at)),
            'verified_at'               => date($dateFormat, strtotime($model->email_verified_at)),
        ];

        return $attributes;
    }
}
