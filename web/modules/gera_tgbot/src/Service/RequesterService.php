<?php

namespace Drupal\gera_tgbot\Service;

use Drupal\node\Entity\Node;

class RequesterService
{
    public function findOrCreate(string $nickname): Node
    {
        $user = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadByProperties(['type' => 'requester', 'title' => $nickname, 'field_type' => 'telegram']);

        if (empty($user)) {
            $user = Node::create(['type' => 'requester', 'title' => $nickname, 'field_type' => 'telegram']);
            $user->save();
        } else {
            $user = reset($user);
        }

        return $user;
    }
}
