<?php

namespace Drupal\gera_tgbot\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class TelegramApiService
{
    const API_URL = 'https://api.telegram.org/bot';

    // Todo: change token and move it to more secure storage :)
    const TOKEN  = '6826231392:AAEJkmOeHWyyb4AV_T6VkkK_E9HJO-swxYA';

    public function __construct(protected HttpClientInterface $httpClient)
    {

    }

    public function downloadAttachment(string $attachmentId): ResponseInterface
    {
        $data = $this->get('getFile', ['file_id' => $attachmentId]);
        $file = $this->httpClient->request(
            'GET',
            'https://api.telegram.org/file/bot' . self::TOKEN . '/' . $data['result']['file_path']
        );

        return $file;
    }

    public function get(string $apiMethod, array $queryParameters = null)
    {
        $response = $this->httpClient->request(
            "GET",
            self::API_URL . self::TOKEN . '/' . $apiMethod . '?' . http_build_query($queryParameters)
        );

        return $response->toArray();
    }

    public function post(string $apiMethod, array $bodyParameters = null)
    {
        return $this->doRequest('POST', $apiMethod, $bodyParameters);
    }

    public function doRequest(string $httpMethod, string $apiMethod, array $bodyParameters = null)
    {
        $response = $this->httpClient->request($httpMethod, self::API_URL . self::TOKEN . '/' . $apiMethod, [
            'body' => $bodyParameters
        ]);

        return $response->toArray();
    }
}
