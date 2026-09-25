<?php

namespace App\Services;

use App\Mail\BookingConfirmationMail;
use App\Models\Bookings;
use App\Models\Payments;
use App\Models\Tickets;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BookingPaymentService
{
    /**
     * Mark a booking as paid, confirm it, issue its tickets and email them.
     *
     * Safe to call more than once (the success redirect and the webhook can both
     * arrive for the same payment): only the first call does any work.
     * Returns true if this call confirmed the booking.
     */
    public function confirm(Bookings $booking, array $paymentData = []): bool
    {
        $confirmedNow = DB::transaction(function () use ($booking, $paymentData) {
            $payment = Payments::where('booking_id', $booking->id)->lockForUpdate()->first();

            if (!$payment || $payment->status === 'paid') {
                return false;
            }

            $payment->update(array_merge([
                'status' => 'paid',
                'method' => 'paymongo',
                'date'   => now(),
            ], array_filter($paymentData)));

            $booking->update(['status' => 'confirmed']);

            foreach ($booking->bookings_seats()->get() as $bookedSeat) {
                Tickets::firstOrCreate(
                    ['booking_id' => $booking->id, 'seat_id' => $bookedSeat->seat_id],
                    ['code' => 'TIX-' . strtoupper(Str::random(10)), 'issued_at' => now()]
                );
            }

            return true;
        });

        if ($confirmedNow) {
            $booking->load('showtime.movie', 'showtime.hall.cinema', 'bookings_seats.seat', 'payment', 'tickets.seat', 'user');

            try {
                Mail::to($booking->user->email)->send(new BookingConfirmationMail($booking));
            } catch (\Exception $e) {
                Log::warning('Failed to send booking confirmation email', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
            }
        }

        return $confirmedNow;
    }
}
