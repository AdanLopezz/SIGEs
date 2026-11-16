<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AvisoEscolar extends Mailable
{
    public function __construct(public readonly int $avisoId) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tienes un aviso escolar en SIGEs');
    }

    public function content(): Content
    {
        return new Content(view: 'correos.aviso-escolar', with: ['enlace' => route('avisos.show', $this->avisoId)]);
    }
}
