<?php

namespace Drupal\eca\Entity;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\eca\Event\ConditionalApplianceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Storage handler for ECA configurations.
 */
class EcaStorage extends ConfigEntityStorage {

  /**
   * Mapped configurations by event class usage.
   *
   * @var array|null
   */
  protected ?array $configByEvents;

  /**
   * The cache backend for storing prebuilt information.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cacheBackend;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    /** @var static $instance */
    $instance = parent::createInstance($container, $entity_type);
    $instance->setCacheBackend($container->get('cache.default'));
    $instance->setLogger($container->get('logger.channel.eca'));
    return $instance;
  }

  /**
   * Loads all ECA configurations that make use of the given event.
   *
   * @param \Drupal\Component\EventDispatcher\Event|\Symfony\Contracts\EventDispatcher\Event $event
   *   The event object.
   * @param string $event_name
   *   The name of the event.
   *
   * @return \Drupal\eca\Entity\Eca[]
   *   The configurations, keyed by entity ID.
   */
  public function loadByEvent(object $event, string $event_name): array {
    if (!isset($this->configByEvents)) {
      $cid = 'eca:storage:events';
      if ($cached = $this->cacheBackend->get($cid)) {
        $this->configByEvents = $cached->data;
      }
      else {
        $this->configByEvents = [];
        $entities = $this->loadMultiple();
        // Sort the configurations by weight and label.
        uasort($entities, [$this->entityType->getClass(), 'sort']);
        /** @var \Drupal\eca\Entity\Eca $eca */
        foreach ($entities as $eca) {
          if (!$eca->status()) {
            continue;
          }
          foreach ($eca->getUsedEvents() as $ecaEvent) {
            $eca_id = $eca->id();
            $plugin = $ecaEvent->getPlugin();
            $plugin_event_name = $plugin->eventName();
            $wildcard = $plugin->lazyLoadingWildcard($eca_id, $ecaEvent);
            if (!isset($this->configByEvents[$plugin_event_name])) {
              $this->configByEvents[$plugin_event_name] = [$eca_id => [$wildcard]];
            }
            elseif (!isset($this->configByEvents[$plugin_event_name][$eca_id])) {
              $this->configByEvents[$plugin_event_name][$eca_id] = [$wildcard];
            }
            elseif (!in_array($wildcard, $this->configByEvents[$plugin_event_name][$eca_id], TRUE)) {
              $this->configByEvents[$plugin_event_name][$eca_id][] = $wildcard;
            }
          }
        }
        $this->cacheBackend->set($cid, $this->configByEvents, CacheBackendInterface::CACHE_PERMANENT, ['config:eca_list']);
        $this->logger->debug('Rebuilt cache array for EcaStorage::loadByEvent().');
      }
    }
    if (empty($this->configByEvents[$event_name])) {
      return [];
    }
    $context = ['%event' => $event_name];
    if ($event instanceof ConditionalApplianceInterface) {
      $eca_ids = [];
      foreach ($this->configByEvents[$event_name] as $eca_id => $wildcards) {
        $wildcard_passed = FALSE;
        $context['%ecaid'] = $eca_id;
        foreach ($wildcards as $wildcard) {
          if ($wildcard_passed = $event->appliesForLazyLoadingWildcard($wildcard)) {
            $eca_ids[] = $eca_id;
            $this->logger->debug('Lazy appliance check for event %event regarding ECA ID %ecaid resulted to apply.', $context);
            break;
          }
        }
        if (!$wildcard_passed) {
          $this->logger->debug('Lazy appliance check for event %event regarding ECA ID %ecaid resulted to not apply.', $context);
        }
      }
    }
    else {
      $eca_ids = array_keys($this->configByEvents[$event_name]);
    }
    if ($eca_ids) {
      $context['%eca_ids'] = implode(', ', $eca_ids);
      $this->logger->debug('Loading ECA configurations for event %event: %eca_ids.', $context);
      return $this->loadMultiple($eca_ids);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL): void {
    $this->configByEvents = NULL;
    parent::resetCache($ids);
  }

  /**
   * Set the cache backend for storing prebuilt information.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function setCacheBackend(CacheBackendInterface $cache_backend): void {
    $this->cacheBackend = $cache_backend;
  }

  /**
   * Set the logger.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger.
   */
  public function setLogger(LoggerChannelInterface $logger): void {
    $this->logger = $logger;
  }

}
