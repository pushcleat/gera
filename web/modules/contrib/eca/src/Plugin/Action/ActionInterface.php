<?php

namespace Drupal\eca\Plugin\Action;

/**
 * Interface for ECA provided actions.
 */
interface ActionInterface {

  /**
   * Whether this action is available outside of the scope of ECA.
   *
   * Most ECA actions are only viable within the scope of ECA. Some actions
   * however may also be useful elsewhere, for example in Views Bulk Operations.
   * For such an action, override this constant in your action class and set
   * it to TRUE. Default is FALSE, which means that this action will only be
   * made available in ECA.
   *
   * @var bool
   */
  public const EXTERNALLY_AVAILABLE = FALSE;

  /**
   * Sets the triggered event that leads to this action.
   *
   * @param \Drupal\Component\EventDispatcher\Event|\Symfony\Contracts\EventDispatcher\Event $event
   *   The triggered event.
   *
   * @return $this
   */
  public function setEvent(object $event): ActionInterface;

  /**
   * Get the triggered event that leads to this action.
   *
   * @return \Drupal\Component\EventDispatcher\Event|\Symfony\Contracts\EventDispatcher\Event
   *   The trigered event.
   */
  public function getEvent(): object;

  /**
   * Gets default configuration for this plugin.
   *
   * @return array
   *   An associative array with the default configuration.
   */
  public function defaultConfiguration(): array;

}
