<?php

namespace Drupal\easy_email\Service;


use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\ProxyClass\File\MimeType\MimeTypeGuesser;
use Drupal\easy_email\Entity\EasyEmailInterface;
use Drupal\easy_email\Event\EasyEmailEvent;
use Drupal\easy_email\Event\EasyEmailEvents;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EmailAttachmentEvaluator implements EmailAttachmentEvaluatorInterface {

  /**
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * @var \Drupal\Core\ProxyClass\File\MimeType\MimeTypeGuesser
   */
  protected $mimeTypeGuesser;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs the EmailTokenEvaluator
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   * @param \Drupal\Core\ProxyClass\File\MimeType\MimeTypeGuesser $mimeTypeGuesser
   */
  public function __construct(EventDispatcherInterface $eventDispatcher, FileSystemInterface $fileSystem, MimeTypeGuesser $mimeTypeGuesser) {
    $this->fileSystem = $fileSystem;
    $this->mimeTypeGuesser = $mimeTypeGuesser;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * @inheritDoc
   */
  public function evaluateAttachments(EasyEmailInterface $email, $save_attachments_to = FALSE) {
    $this->eventDispatcher->dispatch(new EasyEmailEvent($email), EasyEmailEvents::EMAIL_PREATTACHMENTEVAL);
    $files = $email->getEvaluatedAttachments();

    // If save attachments has been enabled, check for any programmatically added files and save them.
    if (!empty($save_attachments_to) && !empty($files)) {
      foreach ($files as $i => $file) {
        $this->saveAttachment($email, $file->uri, $save_attachments_to);
        unset($files[$i]); // This will get re-added in the direct files below.
      }
    }

    // Files attached directly to email entity
    if ($email->hasField('attachment')) {
      $attachments = $email->getAttachments();
      if (!empty($attachments)) {
        foreach ($attachments as $attachment) {
          $file = [
            'filepath' => $path,
            'filename' => $this->fileSystem->basename($path),
            'filemime' => $this->mimeTypeGuesser->guessMimeType($path),
          ];
         $files[] = $file;
        }
      }
    }

    // Dynamic Attachments
    if ($email->hasField('attachment_path')) {
      $attachment_paths = $email->getAttachmentPaths();
      if (!empty($attachment_paths)) {
        foreach ($attachment_paths as $path) {
          // Relative paths that start with '/' get messed up by the realpath call below.
          if (strpos($path, '/') === 0) {
            $path = substr($path, 1);
          }
          $realpath = $this->fileSystem->realpath($path);
          if (!file_exists($realpath)) {
            continue;
          }

          if (!empty($save_attachments_to) && $email->hasField('attachment')) {
            $this->saveAttachment($email, $realpath, $save_attachments_to);
          }

          $file = [
            'filepath' => $path,
            'filename' => $this->fileSystem->basename($path),
            'filemime' => $this->mimeTypeGuesser->guessMimeType($path),
          ];
          $files[] = $file;
        }
      }
    }

    $email->setEvaluatedAttachments($files);

    $this->eventDispatcher->dispatch(new EasyEmailEvent($email), EasyEmailEvents::EMAIL_ATTACHMENTEVAL);
  }

  /**
   * @param \Drupal\easy_email\Entity\EasyEmailInterface $email
   * @param \Drupal\file\FileInterface $file
   */
  protected function saveAttachment(EasyEmailInterface $email, $source, $dest_directory) {
    \Drupal::service('file_system')->prepareDirectory($dest_directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    $file_entity = \Drupal::service('file.repository')->writeData(file_get_contents($source), $dest_directory . '/' . $this->fileSystem->basename($source));
    $email->addAttachment($file_entity->id());
  }

}
