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

        // Log de debug : requête et réponse brute de l'API FCM
        $log = [
            'date' => date('c'),
            'to' => $fcmToken,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'payload' => $payload,
        ];
        try {
            $response = $this->httpClient->request('POST', 'https://fcm.googleapis.com/fcm/send', [
                'headers' => [
                    'Authorization' => 'key=' . $this->serverKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);
            $log['status'] = $response->getStatusCode();
            $log['response'] = $response->getContent(false);
        } catch (\Throwable $e) {
            $log['error'] = $e->getMessage();
        }
        file_put_contents(__DIR__.'/../../var/log/fcm.log', json_encode($log, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n\n", FILE_APPEND);

        return isset($log['status']) && $log['status'] === 200;
    }
}
