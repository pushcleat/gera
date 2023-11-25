<?php

namespace Drupal\gera_tgbot\Service;

use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

class DraftService
{
    public function find(string $username, bool $reset): ?Node
    {
        $lastFinished = $this->findLastFinished($username);
        $unfinised = $this->findUnfinished($username);

        if ($unfinised && $reset) {
            $unfinised->delete();
            return null;
        }

        if ($lastFinished && !$unfinised && !$reset) {
            return $lastFinished;
        }

        return $unfinised;
    }

    protected function findUnfinished(string $username)
    {
        $draftTitle = '[DRAFT] Request from ' . $username;
        $draft = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadByProperties(['type' => 'request', 'title' => $draftTitle]);

        return empty($draft) ? null : reset($draft);
    }

    public function findLastFinished(string $username): ?Node
    {
        $title =  'Request from @' . $username;
        $requests = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadByProperties(['type' => 'request', 'title' => $title]);

        return empty($requests) ? null : end($requests);
    }

    public function create(Node $contact, string $username): Node
    {
        $draftTitle = '[DRAFT] Request from ' . $username;
        $draft = Node::create(['type' => 'request', 'title' => $draftTitle]);
        $draft->set('field_contact', ['target_id' => $contact->id()]);
        $draft->save();

        return $draft;
    }

    public function addData(Node $draft, array $data, ?File $file): string
    {
        $fields = $draft->toArray();

        if (str_starts_with($fields['title'][0]['value'], '[DRAFT]')) {
            if (empty($fields['field_country'])) {
                $this->attachCountry($draft, $data['message']['text'] ?? $data['message']['caption'] ?? 'N/A');

                return 'country';
            }

            if (empty($fields['field_bank'])) {
                $this->attachBank($draft, $data['message']['text'] ?? $data['message']['caption'] ?? 'N/A');

                return 'bank';
            }

            $draft->set('title', 'Request from @' . $data['message']['chat']['username']);
        }

        $this->addAdditionalData($draft, $data, $file);
        $draft->save();

        return 'finish';
    }

    public function addAdditionalData(Node $draft, array $data, $file): string
    {
        $fields = $draft->toArray();
        $now = (new \DateTime())->format('d.m.Y H:i:s');
        $bodyPrefix = '';

        if (!empty($fields['body'][0]['value'])) {
            $bodyPrefix .= $fields['body'][0]['value'] . "\n\nUpdated " . $now . "\n";
        }

        $draft->set('body', $bodyPrefix . ($data['message']['text'] ?? $data['message']['caption']));

        if ($file) {
            $docs = $fields['field__documents'] ?? [];
            $docs[] = ['target_id' => $file->id()];
            $draft->set('field__documents', $docs);
        }

        $draft->save();

        return 'finish';
    }

    protected function attachCountry(Node $draft, string $countryName): Node
    {
        $country = $this->getOrCreateCountry($countryName);
        $draft->set('field_country', ['target_id' => $country->id()]);
        $draft->save();

        return $draft;
    }

    protected function attachBank(Node $draft, string $bankName): Node
    {
        $bank = $this->getOrCreateBank($bankName);
        $draft->set('field_bank', ['target_id' => $bank->id()]);
        $draft->save();

        return $draft;
    }

    protected function getOrCreateCountry(string $country): Term
    {
        return $this->getOrCreateTerm('countries', $country);
    }

    protected function getOrCreateBank(string $bank): Term
    {
        return $this->getOrCreateTerm('banks', $bank);
    }

    protected function getOrCreateTerm(string $entity, string $term): Term
    {
        $terms = \Drupal::entityTypeManager()
            ->getStorage('taxonomy_term')
            ->loadByProperties(['vid' => $entity, 'name' => $term]);

        if (empty($terms)) {
            $result = Term::create(['vid' => $entity, 'name' => $term]);
            $result->save();
            return $result;
        }

        return reset($terms);
    }
}
