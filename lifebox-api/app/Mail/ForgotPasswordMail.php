<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class ForgotPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Holds URL instance
     */
    public $tokenURL;

    /**
     * Holds user instance
     */
    public $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $tokenURL, User $user)
    {
        $this->tokenURL = $tokenURL;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('no-reply@lifebox.net.au')->markdown('mail.ForgotPassword');
    }
}
