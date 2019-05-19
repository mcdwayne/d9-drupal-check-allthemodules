<?php
/**
 * Unit Tests for the RedirectDatabaseStorage class.
 * User: rsimmons
 * Date: 10/12/17
 * Time: 2:36 PM
 * PHPUnit_Framework_TestCase
 */
namespace Drupal\Tests\redirect_extensions\Unit;

use Drupal\redirect_extensions\RedirectDatabaseStorage;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Class RedirectDatabaseStorageTest
 *
 * @coversDefaultClass \Drupal\redirect_extensions\RedirectDatabaseStorage
 * @group redirect_extensions
 */
class RedirectDatabaseStorageTest extends UnitTestCase {

  /**
   * @covers ::redirectExists
   * @dataProvider addDataProvider
   *
   */
  public function testRedirectExists($count, $expected) {

    // Create mocks for the Drupal classes used by testRedirect;
    $connectionProphecy = $this->prophesize(Connection::class);
    $accountProphecy = $this->prophesize(AccountInterface::class);
    $queryProphecy = $this->prophesize(SelectInterface::class);
    $resultProphecy = $this->prophesize(StatementInterface::class);

    $redirect_id = "1";

    $resultProphecy->rowCount()->willReturn($count);

    $queryProphecy->fields('r')->shouldBeCalledTimes(1);
    $queryProphecy->condition('rid', $redirect_id, '=')->shouldBeCalledTimes(1);
    $queryProphecy->execute()->willReturn($resultProphecy->reveal());

    $connectionProphecy->select("redirect_extensions", "r")
      ->willReturn($queryProphecy->reveal());

    $redirectStorage = new RedirectDatabaseStorage($connectionProphecy->reveal(),
      $accountProphecy->reveal());

    self::assertEquals($expected, $redirectStorage->redirectExists($redirect_id));

  }

  public function addDataProvider()
  {
    return [
      [0, FALSE],
      [1, TRUE],
      [2, TRUE]
    ];
  }


}
