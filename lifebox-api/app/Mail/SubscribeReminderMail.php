<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

use function Symfony\Component\Translation\t;

class SubscribeReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var User */
    private $user;
    /**
     * Create a new message instance.
     *
     * @return void
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
    public function build()
    {   
        $subscribeLink = config('app.web_url') . "/main/payment";
        $downloadLink = URL::signedRoute('files.downloadAll', ['user' => $this->user->id]);
        $unSubscribeLink = URL::signedRoute('mail.unsubscribe', ['user' => $this->user->id]);
        $terminateLink = URL::signedRoute('account.terminate', ['user' => $this->user->id]);

        return $this
            ->subject('Resubscribe to Lifebox!')
            ->markdown('mail.SubscribeReminder', [
                'name' => $this->user->first_name ?? $this->user->email,
                'subcribe_link' => $subscribeLink,
                'download_link' => $downloadLink,
                'unsubcribe_link' => $unSubscribeLink,
                'terminate_link' => $terminateLink
            ]);
    }
}
