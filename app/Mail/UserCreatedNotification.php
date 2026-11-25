<?php

namespace App\Mail;
use Illuminate\Support\Facades\Log;
use App\Services\RegionalPocContacts;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserCreatedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $password;

    public function __construct($user, $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

   public function build()
{
    $designationId = $this->user->designation_id ?? null;
    Log::info('User designation_id in UserCreatedNotification:', ['designation_id' => $designationId]);

    if ($designationId && in_array($designationId, [33,34,35,25, 26, 27])) {
    Log::info("1");
        $view = 'emails.head_created_notification';
    } else {
    Log::info("2");
        $view = 'emails.user_created_notification';
    }

    return $this->subject('Your Account Details')
                ->view($view)
                ->with([
                    'user' => $this->user,
                    'password' => $this->password,
                    'regionalPocs' => (new RegionalPocContacts(config('account_email.regional_pocs', [])))->forUser($this->user),
                    'googlePlayUrl' => config('account_email.google_play_url'),
                    'appStoreUrl' => config('account_email.app_store_url'),
                ]);
}

}
