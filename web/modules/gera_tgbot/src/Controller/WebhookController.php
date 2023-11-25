<?php

namespace Drupal\gera_tgbot\Controller;

use Drupal\gera_tgbot\Service\TelegramApiService;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class WebhookController
{
    protected TelegramApiService $api;

    public function __construct()
    {
        $this->api = new TelegramApiService(HttpClient::create());
    }

    public function webhook(Request $request): JsonResponse
    {
        $body = $request->toArray();

        $this->api->post('sendMessage', [
            'chat_id' => $body['message']['chat']['id'],
            'text' => $body['message']['text']
        ]);

        return new JsonResponse(['response' => true]);
    }
}
