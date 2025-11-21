<?php

namespace Tests\Unit;

use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketData;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTicketData;

    public function test_compute_fare_sums_seat_prices()
    {
        $ticket = $this->createTicketWithSeats([120000, 130000, 90000]);
        $service = app(PaymentService::class);

        $fare = $service->computeFare($ticket);

        $this->assertSame(340000, $fare['baseFare']);
        $this->assertSame(0, $fare['discount']);
        $this->assertSame(0, $fare['surcharge']);
        $this->assertSame(340000, $fare['totalAmount']);
        $this->assertSame('VND', $fare['currency']);
    }

    public function test_compute_fare_supports_discount_and_surcharge_overrides()
    {
        $ticket = $this->createTicketWithSeats([50000]);
        $service = app(PaymentService::class);

        $fare = $service->computeFare($ticket, ['discount' => 10000, 'surcharge' => 5000]);

        $this->assertSame(50000, $fare['baseFare']);
        $this->assertSame(10000, $fare['discount']);
        $this->assertSame(5000, $fare['surcharge']);
        $this->assertSame(45000, $fare['totalAmount']);
    }

    public function test_compute_fare_falls_back_to_default_amount()
    {
        $ticket = $this->createTicketWithSeats([]);
        $service = app(PaymentService::class);
        $defaultFare = (int) round((float) config('payments.default_fare_vnd'));

        $fare = $service->computeFare($ticket);

        $this->assertSame($defaultFare, $fare['baseFare']);
        $this->assertSame(0, $fare['discount']);
        $this->assertSame(0, $fare['surcharge']);
        $this->assertSame($defaultFare, $fare['totalAmount']);
    }
}
