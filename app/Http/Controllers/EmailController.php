<?php

namespace App\Http\Controllers;
use App\Mail\Maling;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function sendEmail()
    {
        // Sample data to pass to the email template
        $data = [
            'title' => 'Hello from Your App!',
            'message' => 'This is a test email sent from Laravel.'
        ];

        // Send the email
        Mail::to('rafaymalik575763@gmail.com')->send(new Maling($data));

        return 'Email sent successfully!';
    }
}
