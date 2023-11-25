<?php

namespace Drupal\eca_access\Event;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\EventDispatcher\Event;
use Drupal\eca\Event\AccessEventInterface;
use Drupal\eca\Event\ConditionalApplianceInterface;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\eca\Token\DataProviderInterface;
use Drupal\eca_access\AccessEvents;

/**
 * Dispatched when being asked for access to create an entity.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 */
class CreateAccess extends Event implements AccessEventInterface, ConditionalApplianceInterface, DataProviderInterface {

  /**
   * An associative array of additional context values.
   *
   * @var array
   */
  protected array $context;

  /**
   * The entity bundle name.
   *
   * @var string
   */
  protected string $entityBundle;

  /**
   * The account that asks for access.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $account;

  /**
   * The access result.
   *
   * @var \Drupal\Core\Access\AccessResultInterface|null
   */
  protected ?AccessResultInterface $accessResult = NULL;

  /**
   * An instance holding event data accessible as Token.
   *
   * @var \Drupal\eca\Plugin\DataType\DataTransferObject|null
   */
  protected ?DataTransferObject $eventData = NULL;

  /**
   * Constructs a new EntityAccess object.
   *
   * @param array $context
   *   An associative array of additional context values. By default it contains
   *   language and the entity type ID:
   *   - entity_type_id - the entity type ID.
   *   - langcode - the current language code.
   * @param string $entity_bundle
   *   The entity bundle name.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account that asks for access.
   */
  public function __construct(array $context, string $entity_bundle, AccountInterface $account) {
    $this->context = $context;
    $this->entityBundle = $entity_bundle;
    $this->account = $account;
  }

  /**
   * Get the additional context.
   *
   * @return array
   *   The additional context.
   */
  public function getContext(): array {
    return $this->context;
  }

  /**
   * Get the entity bundle name.
   *
   * @return string
   *   The entity bundle name.
   */
  public function getEntityBundle(): string {
    return $this->entityBundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount(): AccountInterface {
    return $this->account;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    [$w_entity_type_ids, $w_bundles, $w_langcodes] = explode(':', $wildcard);

    if (($w_entity_type_ids !== '*') && !in_array($this->context['entity_type_id'], explode(',', $w_entity_type_ids), TRUE)) {
      return FALSE;
    }

    if (($w_bundles !== '*') && !in_array($this->entityBundle, explode(',', $w_bundles), TRUE)) {
      return FALSE;
    }

    if (($w_langcodes !== '*') && !in_array($this->context['langcode'], explode(',', $w_langcodes), TRUE)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    if (!empty($arguments['entity_type_id']) && $arguments['entity_type_id'] !== '*') {
      $contains_entity_type_id = FALSE;
      foreach (explode(',', $arguments['entity_type_id']) as $c_entity_type_id) {
        $c_entity_type_id = strtolower(trim($c_entity_type_id));
        if ($contains_entity_type_id = ($c_entity_type_id === $this->context['entity_type_id'])) {
          break;
        }
      }
      if (!$contains_entity_type_id) {
        return FALSE;
      }
    }

    if (!empty($arguments['bundle']) && $arguments['bundle'] !== '*') {
      $contains_bundle = FALSE;
      foreach (explode(',', $arguments['bundle']) as $c_bundle) {
        $c_bundle = strtolower(trim($c_bundle));
        if ($contains_bundle = ($c_bundle === $this->entityBundle)) {
          break;
        }
      }
      if (!$contains_bundle) {
        return FALSE;
      }
    }

    if (!empty($arguments['langcode']) && $arguments['langcode'] !== '*') {
      $contains_langcode = FALSE;
      foreach (explode(',', $arguments['langcode']) as $c_langcode) {
        $c_langcode = trim($c_langcode);
        if ($contains_langcode = ($c_langcode === $this->context['langcode'])) {
          break;
        }
      }
      if (!$contains_langcode) {
        return FALSE;
      }
    }

    // Initialize with a neutral result.
    $this->accessResult = AccessResult::neutral();

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessResult(): ?AccessResultInterface {
    return $this->accessResult;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessResult(AccessResultInterface $result): CreateAccess {
    $this->accessResult = $result;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getData(string $key): ?DataTransferObject {
    if ($key === 'event') {
      if (!isset($this->eventData)) {
        $data = [
          'machine-name' => AccessEvents::CREATE,
          'uid' => $this->getAccount()->id(),
          'context' => [],
        ];
        $context = $this->getContext();
        foreach ($context as $k => $v) {
          if (is_scalar($v)) {
            $data['context'][$k] = $v;
          }
        }
        if (isset($context['entity_type_id'])) {
          $data['entity-type'] = $context['entity_type_id'];
        }
        $data['entity-bundle'] = $this->getEntityBundle();
        $this->eventData = DataTransferObject::create($data);
      }

      return $this->eventData;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasData(string $key): bool {
    return $this->getData($key) !== NULL;
  }

}
