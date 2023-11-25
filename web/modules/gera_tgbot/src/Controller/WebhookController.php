<?php

namespace Drupal\gera_tgbot\Controller;

use Drupal;
use Drupal\field\Entity\FieldConfig;
use Drupal\gera_tgbot\Service\DraftService;
use Drupal\gera_tgbot\Service\RequesterService;
use Drupal\gera_tgbot\Service\TelegramApiService;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class WebhookController
{
    protected TelegramApiService $api;
    protected RequesterService $requesterService;
    protected DraftService $draftService;

    public function __construct()
    {
        $this->api = new TelegramApiService(HttpClient::create());
        $this->requesterService = new RequesterService();
        $this->draftService = new DraftService();
    }

    public function webhook(Request $request): JsonResponse
    {
        $body = $request->toArray();
        $contact = $this->requesterService->findOrCreate($body['message']['chat']['username']);
        $draft = $this->draftService->find($body['message']['chat']['id']);
        if (!$draft) {
            $this->draftService->create($contact, $body['message']['chat']['id']);
            $stage = 'created';
        } else {
            $stage = $this->draftService->attachData($draft, $body);
        }

        $textMap = [
            'created' => "Вас приветствует бот для помощи в оформлении банковского счёта в другой стране. Пожалуйста, укажите страну, в которой у вас возникли проблемы с открытием счёта.",
            'country' => "Пожалуйста, укажите банк, в котором у вас возникли проблемы.",
            'bank' => "Пожалуйста, опишите проблему, с которой вы столкнулись.",
            'finish' => "Спасибо за обращение! Если у нас возникнут вопросы, мы свяжемся с вами."
        ];

        $this->api->post('sendMessage', [
            'chat_id' => $body['message']['chat']['id'],
            'text' => $textMap[$stage]
        ]);

        return new JsonResponse(['response' => true]);
    }
}
