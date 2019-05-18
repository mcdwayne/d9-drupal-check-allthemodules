<?php

namespace Drupal\Tests\commander\Unit;

use Drupal\commander\Contracts\CommandInterface;
use Drupal\commander\Plugin\CommandHandlerInterface;
use Drupal\commander\Plugin\CommandHandlerManager;
use Drupal\commander\Service\DefaultCommandBus;
use Drupal\Tests\UnitTestCase;

/**
 * Class DefaultCommandBusTest.
 *
 * @group commander
 */
class DefaultCommandBusTest extends UnitTestCase {

  /**
   * Command.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   *
   * @see \Drupal\commander\Contracts\CommandInterface
   */
  protected $command;

  /**
   * Command handler.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   *
   * @see \Drupal\commander\Plugin\CommandHandlerInterface
   */
  protected $commandHandler;

  /**
   * Command handler manager.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   *
   * @see \Drupal\commander\Plugin\CommandHandlerManager
   */
  protected $commandHandlerManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->command = $this->getMock(CommandInterface::class);
    $this->commandHandler = $this->getMock(CommandHandlerInterface::class);
    $this->commandHandlerManager = $this->getMockBuilder(CommandHandlerManager::class)
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Command execution test.
   */
  public function testCommandBusTriggersCommandExecution() {
    $expectedResult = 'foo';
    $commandHandlerId = 'bar';

    $this->setMockExpectations($expectedResult, $commandHandlerId);

    $commandBus = new DefaultCommandBus($this->commandHandlerManager);

    $this->assertSame($expectedResult, $commandBus->execute($this->command));
    $this->verifyMockObjects();
  }

  /**
   * Sets mock expectations.
   */
  protected function setMockExpectations($return, $pluginId) {
    $this->command->expects($this->once())
      ->method('handlerPluginId')
      ->willReturn($pluginId);

    $this->commandHandler->expects($this->once())
      ->method('execute')
      ->with($this->command)
      ->willReturn($return);

    $this->commandHandlerManager->expects($this->once())
      ->method('createInstance')
      ->with($pluginId)
      ->willReturn($this->commandHandler);
  }

}
