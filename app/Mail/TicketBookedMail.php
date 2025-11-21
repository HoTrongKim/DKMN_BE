<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketBookedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $data)
    {
    }

    public function build()
    {
        $code = $this->data['customer']['bookingCode'] ?? '';
        $subject = trim(sprintf('Xác nhận đặt vé %s - DKMN', $code));

        return $this->subject($subject !== '' ? $subject : 'Xác nhận đặt vé DKMN')
            ->view('emails.dkmn_booking')
            ->with(['data' => $this->data]);
    }
}
