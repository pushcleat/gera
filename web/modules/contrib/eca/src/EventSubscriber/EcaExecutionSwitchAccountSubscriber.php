<?php

namespace Drupal\eca\EventSubscriber;

use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\eca\EcaEvents;
use Drupal\eca\Event\AfterInitialExecutionEvent;
use Drupal\eca\Event\BeforeInitialExecutionEvent;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountSwitcherInterface;

/**
 * Switches to a different user account, if specified.
 */
class EcaExecutionSwitchAccountSubscriber extends EcaBase {

  /**
   * The account switcher service.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected AccountSwitcherInterface $accountSwitcher;

  /**
   * Flag indicating whether the user objects have been initialized.
   *
   * @var bool
   */
  protected static bool $initialized = FALSE;

  /**
   * The user account to use for model execution.
   *
   * @var \Drupal\Core\Session\AccountInterface|null
   */
  protected static ?AccountInterface $modelUser = NULL;

  /**
   * The original user account of the current session.
   *
   * @var \Drupal\Core\Session\AccountInterface|null
   */
  protected static ?AccountInterface $sessionUser = NULL;

  /**
   * Get the service instance of this class.
   *
   * @return \Drupal\eca\EventSubscriber\EcaExecutionSwitchAccountSubscriber
   *   The service instance.
   */
  public static function get(): EcaExecutionSwitchAccountSubscriber {
    return \Drupal::service('eca.execution.switch_account_subscriber');
  }

  /**
   * Set the account switcher.
   *
   * @param \Drupal\Core\Session\AccountSwitcherInterface $account_switcher
   *   The account switcher.
   */
  public function setAccountSwitcher(AccountSwitcherInterface $account_switcher): void {
    $this->accountSwitcher = $account_switcher;
  }

  /**
   * Initializes the model user to switch to.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function initializeUser(ConfigFactoryInterface $config_factory, AccountInterface $current_user): void {
    if (self::$initialized) {
      // Already initialized.
      return;
    }
    self::$initialized = TRUE;

    $uid = (string) $config_factory->get('eca.settings')->get('user');
    if ($uid === '') {
      // Nothing to do.
      return;
    }

    $uid = trim((string) $this->tokenService->replaceClear($uid));
    $user = NULL;
    $storage = $this->entityTypeManager->getStorage('user');
    if (ctype_digit(strval(($uid)))) {
      $user = $storage->load($uid);
    }
    elseif (Uuid::isValid($uid)) {
      $user = reset($storage->loadByProperties(['uuid' => $uid]));
    }
    if ($user) {
      self::$modelUser = $user;
      self::$sessionUser = $storage->load($current_user->id());
    }
    else {
      \Drupal::logger('eca')->error("A different user is specified in the global settings to be switched to when executing ECA models, but the user does not exist. Falling back to default behavior, which is execution using the current user. You need to make sure that the specified user exists.");
    }
  }

  /**
   * Subscriber method before initial execution.
   *
   * Adds the event to the stack, and the form entity to the Token service.
   *
   * @param \Drupal\eca\Event\BeforeInitialExecutionEvent $before_event
   *   The according event.
   */
  public function onBeforeInitialExecution(BeforeInitialExecutionEvent $before_event): void {
    if (self::$modelUser) {
      $this->accountSwitcher->switchTo(self::$modelUser);
      $switch_account = TRUE;
      $before_event->setPrestate('switch_account', $switch_account);
    }
    if (self::$sessionUser) {
      $this->tokenService->addTokenData('session_user', self::$sessionUser);
    }
  }

  /**
   * Subscriber method after initial execution.
   *
   * Removes the form data provider from the Token service.
   *
   * @param \Drupal\eca\Event\AfterInitialExecutionEvent $after_event
   *   The according event.
   */
  public function onAfterInitialExecution(AfterInitialExecutionEvent $after_event): void {
    if ($after_event->getPrestate('switch_account')) {
      $this->accountSwitcher->switchBack();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[EcaEvents::BEFORE_INITIAL_EXECUTION][] = [
      'onBeforeInitialExecution',
      500,
    ];
    $events[EcaEvents::AFTER_INITIAL_EXECUTION][] = [
      'onAfterInitialExecution',
      -500,
    ];
    return $events;
  }

}
