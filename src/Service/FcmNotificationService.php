<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class FcmNotificationService
{
    private $httpClient;
    private $serverKey;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->serverKey = 'X-yFnvvMxAj9Wo4ybWAV_pGgadF5HjroRVhx1muGOsc'; // Mets ici ta clé "Server key" FCM
    }

    public function sendPush($fcmToken, $title, $body, $data = [])
    {
        $payload = [
            'to' => $fcmToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'icon' => '/favicon.ico'
            ],
            'data' => $data
        ];

        $response = $this->httpClient->request('POST', 'https://fcm.googleapis.com/fcm/send', [
            'headers' => [
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        return $response->getStatusCode() === 200;
    }
}
