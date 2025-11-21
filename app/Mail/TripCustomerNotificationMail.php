<?php

namespace App\Mail;

use App\Models\ChuyenDi;
use App\Models\NguoiDung;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TripCustomerNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $payload;

    public function __construct(NguoiDung $recipient, ChuyenDi $trip, string $messageBody, array $summary = [])
    {
        $this->payload = [
            'customerName' => $recipient->ho_ten ?? $recipient->email ?? 'Quý khách',
            'messageBody' => $messageBody,
            'route' => $summary['route'] ?? trim(($trip->tramDi->ten ?? '') . ' → ' . ($trip->tramDen->ten ?? '')),
            'operator' => $summary['operator'] ?? ($trip->nhaVanHanh->ten ?? null),
            'vehicle' => $summary['vehicle'] ?? ($trip->nhaVanHanh->loai ?? null),
            'departure' => $summary['departure'] ?? optional($trip->gio_khoi_hanh)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y'),
            'arrival' => $summary['arrival'] ?? optional($trip->gio_den)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y'),
        ];
    }

    public function build()
    {
        $subject = sprintf('Thông báo chuyến đi %s', $this->payload['route']);

        return $this->subject($subject)
            ->view('emails.trip_notification')
            ->with($this->payload);
    }
}
