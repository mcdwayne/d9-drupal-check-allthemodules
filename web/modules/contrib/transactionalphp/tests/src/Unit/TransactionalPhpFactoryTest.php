<?php

namespace Drupal\Tests\transactionalphp\Unit;

use Drupal\Tests\transactionalphp\Mockers;
use Drupal\transactionalphp\TransactionalPhpFactory;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Transactional PHP factory.
 *
 * @group transactionalphp
 */
class TransactionalPhpFactoryTest extends UnitTestCase {

  use Mockers;

  /**
   * Test factory.
   *
   * @covers \Drupal\transactionalphp\TransactionalPhpFactory::get
   */
  public function testFactory() {
    $connection = $this->mockDatabaseConnection('default', 'default');
    $this->depth = 3;

    $php = (new TransactionalPhpFactory())->get($connection);
    $this->assertEquals($connection->transactionDepth(), $php->getDepth(), 'Depth not properly set.');
  }

}
