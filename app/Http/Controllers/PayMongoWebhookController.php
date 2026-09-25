<?php

namespace App\Http\Controllers;

use App\Models\Bookings;
use App\Models\Payments;
use App\Services\BookingPaymentService;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayMongoWebhookController extends Controller
{
    private PayMongoService $payMongo;

    public function __construct()
    {
        $this->payMongo = new PayMongoService();
    }

    /**
     * Handle PayMongo webhook events
     *
     * Payload shape: { data: { type: "event", attributes: { type: "<event name>", data: { <resource> } } } }
     */
    public function handle(Request $request)
    {
        // Verify webhook signature
        $signature = $request->header('Paymongo-Signature');
        $payload = $request->getContent();

        if (!$signature || !$this->payMongo->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Invalid PayMongo webhook signature', [
                'signature' => $signature,
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $eventType = $request->input('data.attributes.type');
        $resource  = $request->input('data.attributes.data', []);

        Log::info('PayMongo webhook received', [
            'event_type'  => $eventType,
            'resource_id' => $resource['id'] ?? null,
        ]);

        if ($eventType === 'checkout_session.payment.paid') {
            return $this->handleCheckoutPaid($resource);
        }

        Log::info('Unhandled PayMongo event type', ['type' => $eventType]);
        return response()->json(['status' => 'received']);
    }

    /**
     * Handle a paid checkout session
     */
    private function handleCheckoutPaid(array $session)
    {
        $sessionId = $session['id'] ?? null;
        $referenceNumber = $session['attributes']['reference_number'] ?? null;

        $booking = $this->findBooking($sessionId, $referenceNumber);

        if (!$booking) {
            Log::error('Booking not found for PayMongo checkout session', [
                'session'   => $sessionId,
                'reference' => $referenceNumber,
            ]);
            return response()->json(['status' => 'received']);
        }

        if (!$this->payMongo->isCheckoutSessionPaid($session)) {
            Log::warning('Checkout session in paid event has no paid payment', [
                'booking_id' => $booking->id,
                'session'    => $sessionId,
            ]);
            return response()->json(['status' => 'received']);
        }

        $payment = $session['attributes']['payments'][0] ?? [];

        $confirmed = (new BookingPaymentService())->confirm($booking, [
            'paymongo_session_id' => $sessionId,
            'paymongo_payment_id' => $payment['id'] ?? null,
            'payment_method_type' => $payment['attributes']['source']['type'] ?? null,
        ]);

        Log::info($confirmed ? 'Booking confirmed via webhook' : 'Booking was already confirmed', [
            'booking_id' => $booking->id,
            'payment_id' => $payment['id'] ?? null,
        ]);

        return response()->json(['status' => 'processed']);
    }

    /**
     * Find the booking for a checkout session: by the session ID stored when the
     * checkout was created, falling back to the reference number ("B{booking_id}-{timestamp}").
     */
    private function findBooking(?string $sessionId, ?string $referenceNumber): ?Bookings
    {
        if ($sessionId) {
            $payment = Payments::where('paymongo_session_id', $sessionId)->first();
            if ($payment && $payment->booking) {
                return $payment->booking;
            }
        }

        if ($referenceNumber && preg_match('/^B(\d+)-/', $referenceNumber, $matches)) {
            return Bookings::find((int) $matches[1]);
        }

        return null;
    }
}
