<?php
namespace App\Service;

use Google\Auth\OAuth2;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FcmNotificationService
{
    private $httpClient;
    private $projectId;
    private $credentialsPath;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->projectId = 'ton-project-id'; // <-- À remplacer par ton vrai project_id
        $this->credentialsPath = __DIR__.'/../../config/firebase-service-account.json'; // <-- À adapter si besoin
    }

    private function getAccessToken(): string
    {
        $json = json_decode(file_get_contents($this->credentialsPath), true);
        $oauth = new OAuth2([
            'audience' => 'https://oauth2.googleapis.com/token',
            'issuer' => $json['client_email'],
            'signingAlgorithm' => 'RS256',
            'signingKey' => $json['private_key'],
            'tokenCredentialUri' => 'https://oauth2.googleapis.com/token',
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        ]);
        $authToken = $oauth->fetchAuthToken();
        return $authToken['access_token'];
    }

    public function sendPush($fcmToken, $title, $body, $data = [])
    {
        $accessToken = $this->getAccessToken();
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $message = [
            'message' => [
                'token' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $data,
            ]
        ];

        // Log de debug : requête et réponse brute de l'API FCM
        $log = [
            'date' => date('c'),
            'to' => $fcmToken,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'payload' => $message,
        ];
        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $message,
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
