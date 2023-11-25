<?php

namespace Drupal\eca_render\Plugin\ECA\Event;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Entity\Eca;
use Drupal\eca\Entity\Objects\EcaEvent;
use Drupal\eca\Event\Tag;
use Drupal\eca\Plugin\ECA\EcaPluginBase;
use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\eca\Plugin\PluginUsageInterface;
use Drupal\eca_render\Event\EcaRenderBlockEvent;
use Drupal\eca_render\Event\EcaRenderContextualLinksEvent;
use Drupal\eca_render\Event\EcaRenderEntityOperationsEvent;
use Drupal\eca_render\Event\EcaRenderExtraFieldEvent;
use Drupal\eca_render\Event\EcaRenderLazyEvent;
use Drupal\eca_render\Event\EcaRenderViewsFieldEvent;
use Drupal\eca_render\RenderEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of ECA render events.
 *
 * @EcaEvent(
 *   id = "eca_render",
 *   deriver = "Drupal\eca_render\Plugin\ECA\Event\RenderEventDeriver"
 * )
 */
class RenderEvent extends EventBase implements PluginUsageInterface {

  /**
   * The block plugin manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected BlockManagerInterface $blockManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * A list of cache backends for invalidation.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface[]
   */
  protected array $cacheBackends = [];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): EcaPluginBase {
    /** @var \Drupal\eca_render\Plugin\ECA\Event\RenderEvent $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->blockManager = $container->get('plugin.manager.block');
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->moduleHandler = $container->get('module_handler');
    $instance->cacheBackends[] = $container->get('cache.render');
    if ($instance->moduleHandler->moduleExists('page_cache')) {
      $instance->cacheBackends[] = $container->get('cache.page');
    }
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    $definitions = [];
    $definitions['block'] = [
      'label' => 'ECA Block',
      'event_name' => RenderEvents::BLOCK,
      'event_class' => EcaRenderBlockEvent::class,
      'tags' => Tag::RUNTIME,
    ];
    $definitions['contextual_links'] = [
      'label' => 'ECA contextual links',
      'event_name' => RenderEvents::CONTEXTUAL_LINKS,
      'event_class' => EcaRenderContextualLinksEvent::class,
      'tags' => Tag::RUNTIME | Tag::CONFIG | Tag::CONTENT,
    ];
    $definitions['entity_operations'] = [
      'label' => 'ECA entity operation links',
      'event_name' => RenderEvents::ENTITY_OPERATIONS,
      'event_class' => EcaRenderEntityOperationsEvent::class,
      'tags' => Tag::RUNTIME | Tag::CONFIG | Tag::CONTENT,
    ];
    $definitions['extra_field'] = [
      'label' => 'ECA Extra field',
      'event_name' => RenderEvents::EXTRA_FIELD,
      'event_class' => EcaRenderExtraFieldEvent::class,
      'tags' => Tag::RUNTIME | Tag::CONTENT,
    ];
    $definitions['views_field'] = [
      'label' => 'ECA Views field',
      'event_name' => RenderEvents::VIEWS_FIELD,
      'event_class' => EcaRenderViewsFieldEvent::class,
      'tags' => Tag::RUNTIME,
    ];
    $definitions['lazy_element'] = [
      'label' => 'ECA lazy element',
      'event_name' => RenderEvents::LAZY_ELEMENT,
      'event_class' => EcaRenderLazyEvent::class,
      'tags' => Tag::RUNTIME,
    ];
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $values = [];
    if ($this->eventClass() === EcaRenderBlockEvent::class) {
      $values += [
        'block_name' => '',
        'block_machine_name' => '',
      ];
    }
    if ($this->eventClass() === EcaRenderEntityOperationsEvent::class || $this->eventClass() === EcaRenderContextualLinksEvent::class || $this->eventClass() === EcaRenderExtraFieldEvent::class) {
      $values += [
        'entity_type_id' => '',
        'bundle' => '',
      ];
    }
    if ($this->eventClass() === EcaRenderContextualLinksEvent::class) {
      $values += [
        'group' => '',
      ];
    }
    if ($this->eventClass() === EcaRenderViewsFieldEvent::class) {
      $values += [
        'name' => '',
      ];
    }
    if ($this->eventClass() === EcaRenderExtraFieldEvent::class) {
      $values += [
        'extra_field_name' => '',
        'extra_field_label' => '',
        'extra_field_description' => '',
        'display_type' => 'display',
        'weight' => '',
        'visible' => FALSE,
      ];
    }
    if ($this->eventClass() === EcaRenderLazyEvent::class) {
      $values += [
        'name' => '',
        'argument' => '',
      ];
    }
    return $values + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    if ($this->eventClass() === EcaRenderBlockEvent::class) {
      $form['block_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Block name'),
        '#default_value' => $this->configuration['block_name'],
        '#description' => $this->t('This block name will be used for being identified in the list of available blocks.'),
        '#required' => TRUE,
        '#weight' => 10,
      ];
    }
    if ($this->eventClass() === EcaRenderContextualLinksEvent::class) {
      $form['group'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Restrict by link group'),
        '#default_value' => $this->configuration['group'],
        '#description' => $this->t('Example: <em>menu</em>'),
        '#weight' => 0,
      ];
    }
    if ($this->eventClass() === EcaRenderEntityOperationsEvent::class || $this->eventClass() === EcaRenderContextualLinksEvent::class || $this->eventClass() === EcaRenderExtraFieldEvent::class) {
      $form['entity_type_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Restrict by entity type ID'),
        '#default_value' => $this->configuration['entity_type_id'],
        '#description' => $this->t('Example: <em>node, taxonomy_term, user</em>'),
        '#weight' => 10,
      ];
      $form['bundle'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Restrict by entity bundle'),
        '#default_value' => $this->configuration['bundle'],
        '#description' => $this->t('Example: <em>article, tags</em>'),
        '#weight' => 20,
      ];
    }
    if ($this->eventClass() === EcaRenderViewsFieldEvent::class) {
      $form['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Field name'),
        '#description' => $this->t('The specified name of the field, as it is configured in the view.'),
        '#default_value' => $this->configuration['name'],
        '#required' => TRUE,
        '#weight' => 10,
      ];
    }
    if ($this->eventClass() === EcaRenderExtraFieldEvent::class) {
      $form['extra_field_name'] = [
        '#type' => 'machine_name',
        '#machine_name' => [
          'exists' => [$this, 'alwaysFalse'],
        ],
        '#title' => $this->t('Machine name of the extra field'),
        '#description' => $this->t('The <em>machine name</em> of the extra field. Must only container lowercase alphanumeric characters and underscores.'),
        '#default_value' => $this->configuration['extra_field_name'],
        '#required' => TRUE,
        '#weight' => -200,
      ];
      $form['extra_field_label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label of the extra field'),
        '#description' => $this->t('The human-readable label of the extra field.'),
        '#default_value' => $this->configuration['extra_field_label'],
        '#required' => TRUE,
        '#weight' => -190,
      ];
      $form['extra_field_description'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Description'),
        '#default_value' => $this->configuration['extra_field_description'],
        '#required' => FALSE,
        '#weight' => -180,
      ];
      $form['display_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Display type'),
        '#options' => [
          'display' => $this->t('View display'),
          'form' => $this->t('Form display'),
        ],
        '#default_value' => $this->configuration['display_type'],
        '#required' => TRUE,
        '#weight' => -170,
      ];
      $form['weight'] = [
        '#type' => 'number',
        '#title' => $this->t('Weight'),
        '#description' => $this->t('The default weight order. Must be an integer number.'),
        '#default_value' => $this->configuration['weight'],
        '#required' => FALSE,
        '#weight' => -160,
      ];
      $form['visible'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Default visible'),
        '#description' => $this->t('When enabled, the extra field will be automatically displayed by default.'),
        '#default_value' => $this->configuration['visible'],
        '#weight' => -150,
      ];
    }
    if ($this->eventClass() === EcaRenderLazyEvent::class) {
      $form['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Element name'),
        '#description' => $this->t('The name of the element, as it was specified in the configured action <em>Render: lazy element</em>. In any successor of this event, you have access to following tokens:<ul><li><strong>[name]</strong>: Contains the name of the element.</li><li><strong>[argument]</strong>: Contains the optional argument for the element.</li></ul>'),
        '#default_value' => $this->configuration['name'],
        '#required' => TRUE,
        '#weight' => 10,
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::validateConfigurationForm($form, $form_state);
    if (($this->getDerivativeId() === 'contextual_links') && !$this->moduleHandler->moduleExists('contextual')) {
      $form_state->setError($form, $this->t("The <em>Contextual Links</em> module must be installed for being able to react upon contextual links."));
    }
    if (($this->getDerivativeId() === 'views_field') && !$this->moduleHandler->moduleExists('views')) {
      $form_state->setError($form, $this->t("The <em>Views</em> module must be installed for being able to react upon ECA Views fields."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    if ($this->eventClass() === EcaRenderBlockEvent::class) {
      $this->configuration['block_name'] = $form_state->getValue('block_name');
      $this->configuration['block_machine_name'] = $form_state->getValue('block_machine_name', strtolower(preg_replace("/[^a-zA-Z0-9]+/", "_", trim($this->configuration['block_name']))));
    }
    if ($this->eventClass() === EcaRenderContextualLinksEvent::class) {
      $this->configuration['group'] = $form_state->getValue('group');
    }
    if ($this->eventClass() === EcaRenderEntityOperationsEvent::class || $this->eventClass() === EcaRenderContextualLinksEvent::class || $this->eventClass() === EcaRenderExtraFieldEvent::class) {
      $this->configuration['entity_type_id'] = $form_state->getValue('entity_type_id');
      $this->configuration['bundle'] = $form_state->getValue('bundle');
    }
    if ($this->eventClass() === EcaRenderViewsFieldEvent::class) {
      $this->configuration['name'] = $form_state->getValue('name');
    }
    if ($this->eventClass() === EcaRenderExtraFieldEvent::class) {
      $this->configuration['extra_field_name'] = $form_state->getValue('extra_field_name');
      $this->configuration['extra_field_label'] = $form_state->getValue('extra_field_label');
      $this->configuration['extra_field_description'] = $form_state->getValue('extra_field_description');
      $this->configuration['display_type'] = $form_state->getValue('display_type');
      $this->configuration['weight'] = $form_state->getValue('weight');
      $this->configuration['visible'] = !empty($form_state->getValue('visible'));
    }
    if ($this->eventClass() === EcaRenderLazyEvent::class) {
      $this->configuration['name'] = $form_state->getValue('name');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function lazyLoadingWildcard(string $eca_config_id, EcaEvent $ecaEvent): string {
    $derivative_id = $this->getDerivativeId();
    $configuration = $ecaEvent->getConfiguration();
    switch ($derivative_id) {

      case 'block':
        return $configuration['block_machine_name'] ?? '*';

      case 'entity_operations':
      case 'contextual_links':
      case 'extra_field':
        if ($derivative_id === 'contextual_links') {
          $wildcard = trim((string) ($configuration['group'] ?? '*'));
          if ($wildcard === '') {
            $wildcard = '*';
          }
          $wildcard .= ':';
        }
        if ($derivative_id === 'extra_field') {
          $wildcard = trim((string) ($configuration['display_type'] ?? '*'));
          $wildcard .= ':';
          $wildcard .= trim((string) ($configuration['extra_field_name'] ?? '*'));
          $wildcard .= ':';
        }
        $wildcard = $wildcard ?? '';
        $entity_type_ids = [];
        if (!empty($configuration['entity_type_id'])) {
          foreach (explode(',', $configuration['entity_type_id']) as $entity_type_id) {
            $entity_type_id = strtolower(trim($entity_type_id));
            if ($entity_type_id !== '') {
              $entity_type_ids[] = $entity_type_id;
            }
          }
        }
        if ($entity_type_ids) {
          $wildcard .= implode(',', $entity_type_ids);
        }
        else {
          $wildcard .= '*';
        }

        $wildcard .= ':';
        $bundles = [];
        if (!empty($configuration['bundle'])) {
          foreach (explode(',', $configuration['bundle']) as $bundle) {
            $bundle = strtolower(trim($bundle));
            if ($bundle !== '') {
              $bundles[] = $bundle;
            }
          }
        }
        if ($bundles) {
          $wildcard .= implode(',', $bundles);
        }
        else {
          $wildcard .= '*';
        }
        return $wildcard;

      case 'views_field':
        $configuration = $ecaEvent->getConfiguration();
        return $configuration['name'] ?? '*';

      default:
        return parent::lazyLoadingWildcard($eca_config_id, $ecaEvent);

    }
  }

  /**
   * {@inheritdoc}
   */
  public function pluginUsed(Eca $eca, string $id): void {
    if (($this->eventClass() === EcaRenderBlockEvent::class) && (method_exists($this->blockManager, 'clearCachedDefinitions'))) {
      $this->blockManager->clearCachedDefinitions();
    }
    if ($this->eventClass() === EcaRenderExtraFieldEvent::class) {
      $this->entityFieldManager->clearCachedFieldDefinitions();
    }
    foreach ($this->cacheBackends as $cache) {
      $cache->invalidateAll();
    }
  }

  /**
   * Helper callback that always returns FALSE.
   *
   * Some machine name fields cannot have a check whether they are already in
   * use. For these elements, this method can be used.
   *
   * @return bool
   *   Always returns FALSE.
   */
  public function alwaysFalse(): bool {
    return FALSE;
  }

}
