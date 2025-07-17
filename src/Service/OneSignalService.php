<?php

// src/Service/OneSignalService.php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OneSignalService
{
    private $client;
    private $appId;
    private $apiKey;

    public function __construct(HttpClientInterface $client, string $appId, string $apiKey)
    {
        $this->client = $client;
        $this->appId = $appId;
        $this->apiKey = $apiKey;
    }

    public function sendNotification(string $pushToken, string $title, string $message): void
    {
        $response = $this->client->request('POST', 'https://onesignal.com/api/v1/notifications', [
            'headers' => [
                'Authorization' => 'Basic ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'app_id' => $this->appId,
                'include_player_ids' => [$pushToken],
                'headings' => ['en' => $title],
                'contents' => ['en' => $message],
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Erreur lors de l’envoi de la notification');
        }
    }
}
