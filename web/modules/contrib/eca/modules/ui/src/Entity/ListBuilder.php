<?php

namespace Drupal\eca_ui\Entity;

use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Defines a class to build a listing of ECA config entities.
 *
 * @see \Drupal\eca\Entity\Eca
 */
class ListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'eca_entities';

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * This flag stores the calculated result for ::showModeller().
   *
   * @var bool|null
   */
  protected ?bool $showModeller;

  /**
   * Constructs a new ListBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, MessengerInterface $messenger) {
    parent::__construct($entity_type, $storage);
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'eca_collection';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['model'] = $this->t('Model');
    if ($this->showModeller()) {
      $header['modeller'] = $this->t('Modeller');
    }
    $header['events'] = $this->t('Events');
    $header['version'] = $this->t('Version');
    $header['status'] = $this->t('Enabled');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\eca\Entity\Eca $eca */
    $eca = $entity;

    $row['model'] = ['#markup' => $eca->label() ?: $eca->id()];
    if ($this->showModeller()) {
      $row['modeller'] = ['#markup' => (string) $eca->get('modeller')];
    }
    $row['events'] = [
      '#theme' => 'item_list',
      '#items' => $eca->getEventInfos(),
    ];
    $row['version'] = ['#markup' => $eca->get('version') ?: $this->t('undefined')];
    $row['status'] = [
      '#markup' => $eca->status() ? $this->t('yes') : $this->t('no'),
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#value'] = $this->t('Save');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);
    $this->messenger->addStatus($this->t('The ordering has been saved.'));
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity): array {
    $operations = parent::getDefaultOperations($entity);

    if ($entity->access('update')) {
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => Url::fromRoute('entity.eca.edit_form', ['eca' => $entity->id()]),
      ];
    }

    return $operations;
  }

  /**
   * Determines whether the modeller info should be displayed or not.
   *
   * @return bool
   *   Returns TRUE if the modeller info should be displayed, FALSE otherwise.
   */
  protected function showModeller(): bool {
    if (!isset($this->showModeller)) {
      $modellers = [];
      /** @var \Drupal\eca\Entity\Eca $eca */
      foreach ($this->storage->loadMultiple() as $eca) {
        if ($eca->get('modeller')) {
          $modellers[$eca->get('modeller')] = TRUE;
        }
        else {
          $modellers['_none'] = TRUE;
        }
      }
      $this->showModeller = count($modellers) > 1;
    }
    return $this->showModeller;
  }

}
