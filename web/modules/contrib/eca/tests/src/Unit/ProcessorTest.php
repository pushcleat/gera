<?php

namespace Drupal\Tests\eca\Unit;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\eca\Entity\Eca;
use Drupal\eca\Entity\Objects\EcaEvent;
use Drupal\eca\Plugin\ECA\Event\EventInterface;
use Drupal\eca\Processor;
use Drupal\eca\Token\TokenInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Unit tests for the ECA processor engine.
 *
 * @group eca
 * @group eca_core
 */
class ProcessorTest extends EcaUnitTestBase {

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * The Token services.
   *
   * @var \Drupal\eca\Token\TokenInterface
   */
  protected TokenInterface $tokenServices;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->tokenService = $this->createMock(TokenInterface::class);
    $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
  }

  /**
   * Tests the recursionThresholdSurpassed without history.
   *
   * @throws \ReflectionException
   */
  public function testRecursionThresholdWithoutHistory(): void {
    $processor = new Processor($this->entityTypeManager, $this->logger, $this->eventDispatcher, 3);
    $method = $this->getPrivateMethod(Processor::class, 'recursionThresholdSurpassed');
    $result = $method->invokeArgs($processor, [$this->getEcaEvent('1')]);
    $this->assertFalse($result);
  }

  /**
   * Tests the recursionThreshold is surpassed.
   *
   * @throws \ReflectionException
   */
  public function testRecursionThresholdSurpassed(): void {
    $processor = new Processor($this->entityTypeManager, $this->logger, $this->eventDispatcher, 2);
    $this->assertTrue($this->isThresholdComplied($processor));
  }

  /**
   * Tests the recursionThreshold is not surpassed.
   *
   * @throws \ReflectionException
   */
  public function testRecursionThresholdNotSurpassed(): void {
    $processor = new Processor($this->entityTypeManager, $this->logger, $this->eventDispatcher, 3);
    $this->assertFalse($this->isThresholdComplied($processor));
  }

  /**
   * Check whether the threshold is complied.
   *
   * @param \Drupal\eca\Processor $processor
   *   The ECA processor service.
   *
   * @return bool
   *   Retruns TRUE, if the recursion threshold got exceeded, FALSE otherwise.
   *
   * @throws \ReflectionException
   */
  private function isThresholdComplied(Processor $processor): bool {
    $method = $this->getPrivateMethod(Processor::class, 'recursionThresholdSurpassed');
    $executionHistory = $this->getPrivateProperty(Processor::class, 'executionHistory');
    $ecaEvent = $this->getEcaEvent('1');
    $ecaEventHistory = [];
    $ecaEventHistory[] = $ecaEvent;
    $ecaEventHistory[] = $this->getEcaEvent('2');
    $ecaEventHistory[] = $this->getEcaEvent('3');
    $ecaEventHistory[] = $ecaEvent;
    $ecaEventHistory[] = $ecaEvent;
    $ecaEventHistory[] = $ecaEvent;
    $executionHistory->setValue($processor, $ecaEventHistory);
    return $method->invokeArgs($processor, [$ecaEvent]);
  }

  /**
   * Gets a EcaEvent initialized with mocks.
   *
   * @param string $id
   *   The ID of the event.
   *
   * @return \Drupal\eca\Entity\Objects\EcaEvent
   *   The mocked event.
   */
  private function getEcaEvent(string $id): EcaEvent {
    $eca = $this->createMock(Eca::class);
    $event = $this->createMock(EventInterface::class);
    return new EcaEvent($eca, $id, 'label', $event);
  }

}
