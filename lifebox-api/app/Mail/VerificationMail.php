<?php

namespace App\Mail;

use App\Models\User;
use App\Services\UrlHelperService;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class VerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Holds the user instance
     *
     * @var \App\Models\User
    */
    private $user;

    /**
     * Contructor for setting the user instance
     *
     * @param \App\Models\User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(UrlHelperService $urlHelperService)
    {
        return $this->markdown('mail.VerificationMail')
                    ->with([
                        'name' => $this->user->first_name . ' ' . $this->user->last_name,
                        'lifeboxEmail' => $this->user->lifebox_email,
                        'email' => $this->user->email,
                        'username' => $this->user->username,
                        'verificationURL' => URL::signedRoute(
                            $urlHelperService->prefixRouteByDbConnection('verification.verify'),
                            ['id' => $this->user->id]
                        )
                    ]);
    }
}
