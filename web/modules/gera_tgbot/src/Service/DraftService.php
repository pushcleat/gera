<?php

namespace Drupal\gera_tgbot\Service;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

class DraftService
{
    public function find(string $userId): ?Node
    {
        $draftTitle = '[DRAFT] Request from ' . $userId;
        $draft = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadByProperties(['type' => 'request', 'title' => $draftTitle]);

        return empty($draft) ? null : reset($draft);
    }

    public function create(Node $contact, string $userId): Node
    {
        $draftTitle = '[DRAFT] Request from ' . $userId;
        $draft = Node::create(['type' => 'request', 'title' => $draftTitle]);
        $draft->set('field_contact', ['target_id' => $contact->id()]);
        $draft->save();

        return $draft;
    }

    public function attachData(Node $draft, array $data): string
    {
        $fields = $draft->toArray();

        if (empty($fields['field_country'])) {
            $this->attachCountry($draft, $data['message']['text']);

            return 'country';
        }

        if (empty($fields['field_bank'])) {
            $this->attachBank($draft, $data['message']['text']);

            return 'bank';
        }

        $draft->set('body', $data['message']['text']);
        $draft->set('title', 'Request from @' . $data['message']['chat']['username']);
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
