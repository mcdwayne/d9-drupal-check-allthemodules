<?php

namespace Drupal\Tests\cron_service\Unit;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\State\StateInterface;
use Drupal\cron_service\CronServiceInterface;
use Drupal\cron_service\CronServiceManager;
use Drupal\cron_service\ScheduledCronServiceInterface;
use Drupal\cron_service\TimeControllingCronServiceInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Interface combines all the testing interfaces.
 */
interface CombinedInterface extends TimeControllingCronServiceInterface, ScheduledCronServiceInterface {

}

/**
 * BaseCronService tests.
 *
 * @group cron_service
 *
 * @coversDefaultClass \Drupal\cron_service\CronServiceManager
 */
class CronServiceManagerTest extends UnitTestCase {

  /**
   * Service injection.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $stateSvc;

  /**
   * Service injection.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->stateSvc = $this
      ->getMockBuilder(StateInterface::class)
      ->getMock();
    $this->logger = $this
      ->getMockBuilder(LoggerChannelInterface::class)
      ->getMock();
  }

  /**
   * Cron service mocks factory.
   *
   * @param string $interface
   *   Service interface.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject|\Drupal\cron_service\CronServiceInterface
   *   Mocked cron service.
   */
  protected function getService(string $interface = CronServiceInterface::class) {
    return $this
      ->getMockBuilder($interface)
      ->getMock();
  }

  /**
   * Cron service processor factory.
   *
   * @param array|null $methods
   *   Methods to mock.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject|\Drupal\cron_service\CronServiceManager
   *   Mocked test object.
   */
  protected function getTestObject(array $methods = NULL) {
    $mock = $this->getMockBuilder(CronServiceManager::class)
      ->setConstructorArgs([
        $this->stateSvc,
        $this->logger,
      ])
      ->setMethods($methods)
      ->getMock();
    $mock->setStringTranslation($this->getStringTranslationStub());
    return $mock;
  }

  /**
   * Tests the class adds and stores, and processes handlers.
   */
  public function testHandlersCanBeAddedAndExecuted() {
    $test_object = $this->getTestObject(['executeHandler']);
    $handlers = [];
    $count = random_int(1, 8);
    for ($i = 0; $i < $count; $i++) {
      $handlers[] = [
        $this->getService(),
        $this->getRandomGenerator()->name(8, TRUE),
      ];
    }

    foreach ($handlers as $handler) {
      $test_object->addHandler(...$handler);
    }

    $test_object->expects(self::exactly(count($handlers)))
      ->method('executeHandler')
      ->withConsecutive(
        ...array_map(
          function ($handler) {
            return [$handler[1], FALSE];
          },
          $handlers
        )
      )
      ->willReturn(TRUE);

    $test_object->execute();
  }

  /**
   * Tests that execution failed on trying to execute non existing handler.
   */
  public function testMissingHandlersDontRuinAnything() {
    self::assertFalse(
      $this->getTestObject()->executeHandler($this->randomMachineName())
    );
    // Even force should fail.
    self::assertFalse(
      $this->getTestObject()->executeHandler($this->randomMachineName(), TRUE)
    );
  }

  /**
   * Tests that cron manager executes the service when force flag is set.
   */
  public function testForceExecutionIgnoresEverything() {
    // Set time to past.
    $this->stateSvc
      ->expects(self::any())
      ->method('get')
      ->willReturn(time() + 86400);

    $services = [
      'id1' => $this->getService(),
      'id2' => $this->getService(TimeControllingCronServiceInterface::class),
      'id3' => $this->getService(ScheduledCronServiceInterface::class),
      'id4' => $this->getService(CombinedInterface::class),
    ];
    $services['id2']->method('shouldRunNow')->willReturn(FALSE);
    $services['id4']->method('shouldRunNow')->willReturn(FALSE);

    $test_object = $this->getTestObject();
    foreach ($services as $id => $service) {
      $service->expects(self::once())->method('execute')->willReturn(TRUE);

      $test_object->addHandler($service, $id);
      // Force execution.
      $test_object->executeHandler($id, TRUE);
    }
  }

  /**
   * Tests that cron service executes when time has come with updating state.
   *
   * Instead of creating a mock with set of expectations. We simple create a
   * fake but working StateInterface implementation and check data is kept
   * between instances.
   */
  public function testExecutionTimeIsStoredInState() {
    // Value for checking the state setter.
    $next_run_time = random_int(0, 10000);
    $svc_name = $this->randomMachineName();
    $this->stateSvc = new StateMock();

    $svc = $this->getService(ScheduledCronServiceInterface::class);
    $svc->expects(self::once())
      ->method('getNextExecutionTime')
      ->willReturn($next_run_time);

    $test_object = $this->getTestObject();
    $test_object->addHandler($svc, $svc_name);
    $test_object->execute();

    self::assertEquals(
      $next_run_time,
      $test_object->getScheduledCronRunTime($svc_name)
    );

    $test_object = $this->getTestObject();
    $test_object->addHandler($svc, $svc_name);
    self::assertEquals(
      $next_run_time,
      $test_object->getScheduledCronRunTime($svc_name)
    );

  }

  /**
   * Test executing service when time is come.
   */
  public function testScheduledServicesMustBeExecutedWhenItsTime() {
    $svc = $this->getService(ScheduledCronServiceInterface::class);
    $svc->expects(self::once())
      ->method('execute')
      ->willReturn(TRUE);

    // Combined should also be executed when it allows to.
    $svc2 = $this->getService(CombinedInterface::class);
    $svc2->expects(self::once())
      ->method('execute')
      ->willReturn(TRUE);
    $svc2->expects(self::any())
      ->method('shouldRunNow')
      ->willReturn(TRUE);

    $svc3 = $this->getService(CombinedInterface::class);
    $svc3->expects(self::never())
      ->method('execute')
      ->willReturn(TRUE);
    $svc3->expects(self::any())
      ->method('shouldRunNow')
      ->willReturn(FALSE);

    $this->stateSvc
      ->expects(self::atLeastOnce())
      ->method('get')
      ->willReturn(0);

    $test_object = $this->getTestObject(['getScheduledCronRunTime']);
    $test_object
      ->method('getScheduledCronRunTime')
      ->withAnyParameters()
      ->willReturn(0);

    $test_object->addHandler($svc, 'task_1');
    $test_object->addHandler($svc2, 'task_2');
    $test_object->addHandler($svc3, 'task_3');
    $test_object->execute();

  }

  /**
   * Tests that cron service skips the execution when time is not come.
   */
  public function testScheduledServiceMustNotBeExecutedBeforeTheirTime() {
    $service = $this->getService(ScheduledCronServiceInterface::class);
    $service->expects(self::never())
      ->method('execute')
      ->willReturn(TRUE);

    // Combined should allow executing but not be actually executed.
    $service2 = $this->getService(CombinedInterface::class);
    $service2->expects(self::never())
      ->method('execute')
      ->willReturn(TRUE);
    $service2->expects(self::any())
      ->method('shouldRunNow')
      ->willReturn(TRUE);

    $test_object = $this->getTestObject(['getScheduledCronRunTime']);
    $test_object
      ->method('getScheduledCronRunTime')
      ->willReturn(time() + 86400);

    $test_object->addHandler($service, 'service_1');
    $test_object->addHandler($service2, 'service_2');
    $test_object->execute();
  }

  /**
   * Tests working with time controlling services.
   */
  public function testItRespectsTimeControllingServices() {
    $svc1 = $this->getService(TimeControllingCronServiceInterface::class);
    $svc1->expects(self::atLeastOnce())
      ->method('shouldRunNow')
      ->willReturn(TRUE);
    $svc1
      ->expects(self::once())
      ->method('execute');

    $svc2 = $this->getService(TimeControllingCronServiceInterface::class);
    $svc2->expects(self::atLeastOnce())
      ->method('shouldRunNow')
      ->willReturn(FALSE);
    $svc2->expects(self::never())
      ->method('execute');

    $test_object = $this->getTestObject();
    $test_object->addHandler($svc1, 'some_task');
    $test_object->addHandler($svc2, 'some_other_task');
    $test_object->execute();
  }

  /**
   * Tests how forcing next runs work.
   */
  public function testForceNextRunWorks() {
    $this->stateSvc = new StateMock();

    $svc_name = $this->randomMachineName();
    $svc = $this->getService();
    $svc->expects(self::once())
      ->method('execute');

    $test_object = $this->getTestObject(['getScheduledCronRunTime']);
    // It's not the time.
    $test_object->expects(self::any())
      ->method('getScheduledCronRunTime')
      ->willReturn(time() + 86400);

    $test_object->addHandler($svc, $svc_name);
    self::assertFalse($test_object->shouldRunNow($svc_name));
    $test_object->forceNextExecution($svc_name);
    self::assertTrue($test_object->shouldRunNow($svc_name));
    $test_object->execute();
  }

}
