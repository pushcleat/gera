<?php

namespace Drupal\gera_tgbot\Service;

use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;

class FileService
{
    public function __construct(protected TelegramApiService $api)
    {

    }

    public function attach(array $body): ?File
    {
        if (!empty($body['message']['photo'])) {
            $lastPhoto = end($body['message']['photo']);
            $file = $lastPhoto['file_id'];
        } else {
            $file = $body['message']['document']['file_id'] ?? null;
        }

        if (!$file) {
            return null;
        }

        $file = $this->api->downloadAttachment($file);
        $directory = 'public://uploads/' . (new \DateTime())->format('Y-m-d');
        /** @var \Drupal\Core\File\FileSystemInterface $filesystem */
        $filesystem = \Drupal::service('file_system');
        $filesystem->prepareDirectory(
            $directory,
            FileSystemInterface:: CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
        );

        $file = $filesystem->saveData(
            $file->getContent(),
            $directory . '/' . basename($file->getInfo()['url']),
            FileSystemInterface::EXISTS_REPLACE
        );

        $file = File::create(['uri' => $file, 'status' => 1]);
        $file->save();

        return $file;
    }
}
