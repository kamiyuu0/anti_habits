<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ResetPasswordInstructions extends Mailable
{
    public function __construct(public User $user, public string $token) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'パスワード再設定のご案内');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.reset_password_instructions', with: [
            'url' => route('password.reset', ['reset_password_token' => $this->token]),
        ]);
    }
}
