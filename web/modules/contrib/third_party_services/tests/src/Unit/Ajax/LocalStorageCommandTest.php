<?php

namespace Drupal\Tests\third_party_services\Ajax;

use Drupal\Tests\UnitTestCase;
use Drupal\third_party_services\Ajax\LocalStorageCommand;

/**
 * Tests of the LocalStorageCommand AJAX command.
 *
 * @group third_party_services
 */
class LocalStorageCommandTest extends UnitTestCase {

  /**
   * Tests of the LocalStorageCommand AJAX command.
   *
   * @param string $method
   *   Method to use.
   * @param array $arguments
   *   Arguments to pass.
   *
   * @dataProvider providerTest
   */
  public function test(string $method, array $arguments) {
    $constructor_args = $arguments;
    array_unshift($constructor_args, $method);

    $return = [
      'command' => 'localStorage',
      'method' => $method,
      'args' => $arguments,
    ];

    /* @var \PHPUnit_Framework_MockObject_MockObject|LocalStorageCommand $mock */
    $mock = $this
      ->getMockBuilder(LocalStorageCommand::class)
      ->setConstructorArgs($constructor_args)
      ->getMock();

    $mock
      ->expects(static::once())
      ->method('render')
      ->willReturn($return);

    static::assertAttributeSame($method, 'method', $mock);
    static::assertAttributeSame($arguments, 'arguments', $mock);
    static::assertSame($return, $mock->render());
  }

  /**
   * Provides data for testing.
   *
   * @return array[]
   *   Sets of arguments for iteration testing.
   */
  public function providerTest(): array {
    return [
      ['setItem', ['key', 'value']],
      ['setItem', ['key']],
      ['clear', []],
    ];
  }

}
