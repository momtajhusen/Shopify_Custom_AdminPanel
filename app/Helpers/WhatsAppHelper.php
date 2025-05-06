<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class WhatsAppHelper
{
    public static function sendImageMessage(string $phoneNumber, string $imageUrl, array $bodyValues): array
    {
        $apiKey = config('services.interakt.api_key'); 
        $url    = config('services.interakt.url');

        $payload = [
            'countryCode'     => '+91',
            'phoneNumber'     => $phoneNumber,
            'fullPhoneNumber' => '',
            'campaignId'      => '',
            'callbackData'    => 'order-vendor',
            'type'            => 'Template',
            'template'        => [
                'name'         => 'vendor_notification',
                'languageCode' => 'en',
                'headerValues' => [ $imageUrl ],
                'bodyValues'   => $bodyValues,
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->post($url, $payload);

        if ($response->successful()) {
            return [
                'status'   => true,
                'message'  => 'Message sent successfully',
                'response' => $response->json(),
            ];
        }

        return [
            'status'   => false,
            'message'  => $response->body(),
            'response' => $response->json() ?? [],
        ];
    }
}
