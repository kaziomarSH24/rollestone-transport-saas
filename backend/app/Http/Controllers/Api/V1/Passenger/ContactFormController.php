<?php

namespace App\Http\Controllers\Api\V1\Passenger;

use App\Http\Controllers\Controller;
use App\Notifications\ContactFormNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class ContactFormController extends Controller
{
    public function submitContactForm(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string',
        ]);

        $companyEmail = tenant()->contact_email;

        if (!$companyEmail) {
            return response_error('Contact email is not configured.', [], 500);
        }

        Notification::route('mail', $companyEmail)
            ->notify(new ContactFormNotification($request->only('name', 'email', 'message')));

        return response_success('Your message has been sent successfully.', []);
    }
}
