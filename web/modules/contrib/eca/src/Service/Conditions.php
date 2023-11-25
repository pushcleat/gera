<?php

namespace Drupal\eca\Service;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eca\Plugin\ECA\Condition\ConditionInterface;
use Drupal\eca\PluginManager\Condition;
use Drupal\eca\Token\TokenInterface;

/**
 * Service class for Drupal core conditions in ECA.
 */
class Conditions {

  use ServiceTrait;

  public const GATEWAY_TYPE_EXCLUSIVE = 0;
  public const GATEWAY_TYPE_PARALLEL = 1;
  public const GATEWAY_TYPE_INCLUSIVE = 2;
  public const GATEWAY_TYPE_COMPLEX = 3;
  public const GATEWAY_TYPE_EVENTBASED = 4;

  /**
   * ECA condition plugin manager.
   *
   * @var \Drupal\eca\PluginManager\Condition
   */
  protected Condition $conditionManager;

  /**
   * Logger channel service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * ECA token service.
   *
   * @var \Drupal\eca\Token\TokenInterface
   */
  protected TokenInterface $token;

  /**
   * Conditions constructor.
   *
   * @param \Drupal\eca\PluginManager\Condition $condition_manager
   *   The ECA condition plugin manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   *   The current user account.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\eca\Token\TokenInterface $token
   *   The ECA token service.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(Condition $condition_manager, LoggerChannelInterface $logger, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $account_proxy, LanguageManagerInterface $language_manager, TokenInterface $token) {
    $this->conditionManager = $condition_manager;
    $this->logger = $logger;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->token = $token;
  }

  /**
   * Returns a sorted list of condition plugins.
   *
   * @return \Drupal\eca\Plugin\ECA\Condition\ConditionInterface[]
   *   The sorted list of consitions.
   */
  public function conditions(): array {
    static $conditions;
    if ($conditions === NULL) {
      $conditions = [];
      foreach ($this->conditionManager->getDefinitions() as $plugin_id => $definition) {
        try {
          $conditions[] = $this->conditionManager->createInstance($plugin_id);
        }
        catch (PluginException $e) {
          // Can be ignored.
        }
      }
    }
    $this->sortPlugins($conditions);
    return $conditions;
  }

  /**
   * Asserts the condition identified by $condition_id in context of an event.
   *
   * @param \Drupal\Component\EventDispatcher\Event|\Symfony\Contracts\EventDispatcher\Event $event
   *   The event in which context the conditions needs to be asserted.
   * @param string|bool $condition_id
   *   The ID of the condition to be asserted or FALSE, if the sequence flow
   *   does not have any condition.
   * @param array|null $condition
   *   An array containing the plugin ID of the condition to be asserted and
   *   the field values of the plugin configuration.
   * @param array $context
   *   An array with context settings of events and successirs for building
   *   meaningful log messages.
   *
   * @return bool
   *   TRUE, if the condition can be asserted, FALSE otherwise.
   */
  public function assertCondition(object $event, $condition_id, ?array $condition, array $context): bool {
    if (empty($condition_id)) {
      $this->logger->info('Unconditional %successorlabel (%successorid) from ECA %ecalabel (%ecaid) for event %event.', $context);
      return TRUE;
    }
    $context['%conditionid'] = $condition_id;
    if ($condition === NULL) {
      $this->logger->error('Non existant condition %conditionid for %successorlabel from ECA %ecalabel (%ecaid) for event %event.', $context);
      return FALSE;
    }
    try {
      /** @var \Drupal\eca\Plugin\ECA\Condition\ConditionInterface $plugin */
      $plugin = $this->conditionManager->createInstance($condition['plugin'], $condition['configuration'] ?? []);
    }
    catch (PluginException $e) {
      // Deliberately ignored, handled below already.
    }
    if (isset($plugin)) {
      // If a config value is an array, we may receive a string from the
      // modeller and have to convert this into an array.
      $pluginConfig = $plugin->getConfiguration();
      $defaultConfig = $plugin->defaultConfiguration();
      foreach ($pluginConfig as $key => $value) {
        if (isset($defaultConfig[$key]) && is_array($defaultConfig[$key])) {
          $pluginConfig[$key] = explode(',', $value);
        }
      }
      $plugin->setConfiguration($pluginConfig);

      if ($plugin instanceof ConditionInterface) {
        $plugin->setEvent($event);
      }
      /** @var \Drupal\Core\Plugin\Context\ContextDefinition $definition */
      foreach ($plugin->getPluginDefinition()['context_definitions'] ?? [] as $key => $definition) {
        // If the field for this context is filled by the model, then use that.
        // Otherwise fall back to the entity of the original event of the
        // current process.
        if (empty($pluginConfig[$key])) {
          switch ($definition->getDataType()) {
            case 'language':
              $token = $this->languageManager->getCurrentLanguage();
              break;

            default:
              $token = 'entity';
          }
        }
        else {
          $token = $pluginConfig[$key];
        }
        if (is_string($token)) {
          $data = $this->token->getTokenData($token);
        }
        else {
          $data = $token;
        }
        try {
          $plugin->setContextValue($key, $data);
        }
        catch (ContextException $e) {
          $this->logger->error('Invalid context data for condition %conditionid for %successorlabel from ECA %ecalabel (%ecaid) for event %event.', $context);
        }
      }
      if ($plugin->reset()->evaluate()) {
        $this->logger->info('Asserted condition %conditionid for %successorlabel from ECA %ecalabel (%ecaid) for event %event.', $context);
        return TRUE;
      }
      $this->logger->info('Not asserting condition %conditionid for %successorlabel from ECA %ecalabel (%ecaid) for event %event.', $context);
    }
    else {
      $this->logger->error('Invalid condition %conditionid for %successorlabel from ECA %ecalabel (%ecaid) for event %event.', $context);
    }
    return FALSE;
  }

}
