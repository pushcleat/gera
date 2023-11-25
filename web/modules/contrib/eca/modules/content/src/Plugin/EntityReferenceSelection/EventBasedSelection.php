<?php

namespace Drupal\eca_content\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\eca_content\Event\ReferenceSelection;
use Drupal\eca_content\Event\ContentEntityEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides event-based access control on entity reference selections.
 *
 * @EntityReferenceSelection(
 *   id = "eca",
 *   label = @Translation("Event-based selection with ECA"),
 *   group = "eca",
 *   weight = 10
 * )
 */
class EventBasedSelection extends SelectionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * Holds the initialized list of referencable entities.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]|null
   */
  public ?iterable $referencableEntities;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity.repository')
    );
  }

  /**
   * Constructs a new selection object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityRepositoryInterface $entity_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->eventDispatcher = $event_dispatcher;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $this->initializeReferencableEntities();
    $target_type = $this->configuration['target_type'];
    $referencable = [];
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    foreach ($this->referencableEntities as $entity) {
      if ($entity->getEntityTypeId() === $target_type) {
        $referencable[$entity->bundle()][$entity->id()] = Html::escape($this->entityRepository->getTranslationFromContext($entity)->label() ?? '');
      }
    }
    return $referencable;
  }

  /**
   * {@inheritdoc}
   */
  public function countReferenceableEntities($match = NULL, $match_operator = 'CONTAINS') {
    $this->initializeReferencableEntities();
    return count($this->referencableEntities);
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids) {
    $this->initializeReferencableEntities();
    $target_type = $this->configuration['target_type'];
    return array_filter($ids, function ($id) use ($target_type) {
      foreach ($this->referencableEntities as $entity) {
        if (($entity->id() == $id) && ($target_type === $entity->getEntityTypeId())) {
          return TRUE;
        }
      }
      return FALSE;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + ['field_name' => NULL];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $entity */
    $entity = $form_object->getEntity();
    // We need to know the field name later on, therefore pass it along.
    $form['field_name'] = [
      '#type' => 'hidden',
      '#value' => $entity->getName(),
      '#weight' => -20,
    ];
    $form['help'] = [
      '#markup' => $this->t('You can react upon this within ECA using the event <em>"Entity reference field selection"</em> and define which entities may be referenced from there.'),
      '#weight' => -10,
    ];
    return $form;
  }

  /**
   * Initializes the referencable entities.
   */
  protected function initializeReferencableEntities(): void {
    if (isset($this->referencableEntities)) {
      // Already initialized.
      return;
    }
    $this->eventDispatcher->dispatch(new ReferenceSelection($this), ContentEntityEvents::REFERENCE_SELECTION);
    if (!isset($this->referencableEntities)) {
      // Fallback to an empty list.
      $this->referencableEntities = [];
    }
  }

}
