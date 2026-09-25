<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class PayMongoService
{
    private Client $client;
    private string $baseUrl = 'https://api.paymongo.com/v1';
    private string $apiKey;
    private string $secretKey;

    public function __construct()
    {
        // Cast so the app still boots when the keys aren't configured yet
        $this->apiKey = (string) config('paymongo.api_key');
        $this->secretKey = (string) config('paymongo.secret_key');
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($this->secretKey . ':'),
            ]
        ]);
    }

    /**
     * Create a PayMongo checkout session for payment
     */
    public function createCheckoutSession(array $data): array
    {
        try {
            $payload = [
                'data' => [
                    'attributes' => [
                        'send_email_receipt' => false,
                        'show_description' => true,
                        'show_line_items' => true,
                        'statement_descriptor' => 'Pampanga',
                        'description' => $data['description'] ?? 'Cinema Booking',
                        'reference_number' => $data['reference_number'],
                        'line_items' => [
                            [
                                'currency' => 'PHP',
                                'amount' => (int) round($data['amount'] * 100),
                                'name' => 'Cinema Ticket',
                                'quantity' => (int)$data['quantity'],
                            ]
                        ],
                        'payment_method_types' => [
                            'card',
                            'gcash',
                        ],
                        'success_url' => $data['success_url'],
                        'cancel_url' => $data['cancel_url'],
                    ]
                ]
            ];

            $response = $this->client->request('POST', $this->baseUrl . '/checkout_sessions', [
                'body' => json_encode($payload),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'accept' => 'application/json',
                    'authorization' => 'Basic ' . base64_encode($this->secretKey . ':'),
                ],
            ]);

            $result = json_decode($response->getBody(), true);
            
            Log::info('PayMongo checkout session response', [
                'full_result' => $result,
                'checkout_url' => $result['data']['attributes']['checkout_url'] ?? 'NOT_FOUND'
            ]);

            return [
                'success' => true,
                'data' => $result['data']
            ];
        } catch (GuzzleException $e) {
            Log::error('PayMongo checkout creation failed', [
                'error' => $e->getMessage(),
                'reference' => $data['reference_number']
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieve a checkout session
     */
    public function retrieveCheckoutSession(string $sessionId): array
    {
        try {
            $response = $this->client->get("/checkout_sessions/{$sessionId}");
            $result = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'data' => $result['data']
            ];
        } catch (GuzzleException $e) {
            Log::error('PayMongo retrieve session failed', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Whether a checkout session has actually been paid.
     *
     * The session's own `status` is only "active" or "expired" — "active" just
     * means the checkout page is still open, not that the customer paid. Payment
     * shows up as a paid entry in `payments` / a succeeded payment intent.
     */
    public function isCheckoutSessionPaid(array $session): bool
    {
        $attributes = $session['attributes'] ?? [];

        foreach ($attributes['payments'] ?? [] as $payment) {
            if (($payment['attributes']['status'] ?? null) === 'paid') {
                return true;
            }
        }

        return ($attributes['payment_intent']['attributes']['status'] ?? null) === 'succeeded';
    }

    /**
     * Create a payment (alternative method)
     */
    public function createPayment(array $data): array
    {
        try {
            $payload = [
                'data' => [
                    'attributes' => [
                        'amount' => $data['amount'] * 100, // Convert to centavos
                        'currency' => 'PHP',
                        'description' => $data['description'],
                        'statement_descriptor' => 'CineMax Cinema',
                        'reference_number' => $data['reference_number'],
                    ]
                ]
            ];

            $response = $this->client->post('/payments', [
                'json' => $payload
            ]);

            $result = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'data' => $result['data']
            ];
        } catch (GuzzleException $e) {
            Log::error('PayMongo payment creation failed', [
                'error' => $e->getMessage(),
                'reference' => $data['reference_number']
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieve payment details
     */
    public function retrievePayment(string $paymentId): array
    {
        try {
            $response = $this->client->get("/payments/{$paymentId}");
            $result = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'data' => $result['data']
            ];
        } catch (GuzzleException $e) {
            Log::error('PayMongo retrieve payment failed', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signatureHeader): bool
    {
        $webhookSecret = (string) config('paymongo.webhook_secret');
        if ($webhookSecret === '') {
            return false;
        }

        // Header format: "t=<timestamp>,te=<test signature>,li=<live signature>"
        $parts = [];
        foreach (explode(',', $signatureHeader) as $pair) {
            [$key, $value] = array_pad(explode('=', trim($pair), 2), 2, '');
            $parts[$key] = $value;
        }

        if (empty($parts['t'])) {
            return false;
        }

        $computedSignature = hash_hmac('sha256', $parts['t'] . '.' . $payload, $webhookSecret);

        foreach (['te', 'li'] as $key) {
            if (!empty($parts[$key]) && hash_equals($computedSignature, $parts[$key])) {
                return true;
            }
        }

        return false;
    }
}
