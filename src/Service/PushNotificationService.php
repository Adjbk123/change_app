<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PushNotificationService
{
    private $onesignalAppId;
    private $onesignalApiKey;
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->onesignalAppId = '21bf691f-ee9d-4d3a-8c40-b15d1a98ebbd';
        $this->onesignalApiKey = 'os_v2_app_eg7wsh7otvgtvdcawforvghlxwfmi4z2sq6etq5atagectnlr6pnpph5ied6p37m7domwaw2qnfttj5z7nhxr6qy7dbgm45iw2y7zrq';
        $this->httpClient = $httpClient;
    }

    public function sendPush($pushToken, $title, $message, $url = null)
    {
        $data = [
            'app_id' => $this->onesignalAppId,
            'include_player_ids' => [$pushToken],
            'headings' => ['fr' => $title],
            'contents' => ['fr' => $message],
        ];
        if ($url) {
            $data['url'] = $url;
        }

        $response = $this->httpClient->request('POST', 'https://onesignal.com/api/v1/notifications', [
            'headers' => [
                'Authorization' => 'Basic ' . $this->onesignalApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);

        return $response->getStatusCode() === 200;
    }
}
