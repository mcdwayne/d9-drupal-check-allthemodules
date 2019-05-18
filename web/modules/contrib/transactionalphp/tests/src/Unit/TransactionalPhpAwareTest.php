<?php

namespace Drupal\Tests\transactionalphp\Unit;

use Drupal\Tests\transactionalphp\Mockers;
use Drupal\Tests\transactionalphp\TestAware;
use Drupal\transactionalphp\TransactionalPhp;
use Drupal\Tests\UnitTestCase;
use Gielfeldt\TransactionalPHP\Indexer;

/**
 * Tests the Transactional PHP factory.
 *
 * @group transactionalphp
 */
class TransactionalPhpAwareTest extends UnitTestCase {

  use Mockers;

  /**
   * Test awareness.
   *
   * @covers \Drupal\transactionalphp\TransactionalPhpAwareTrait
   * @covers \Drupal\transactionalphp\TransactionalPhpIndexerAwareTrait
   */
  public function testTransactionalPhpAware() {

    $test = new TestAware();
    $connection = $this->mockDatabaseConnection('default', 'default');
    $php = new TransactionalPhp($connection);
    $indexer = new Indexer($php);

    $test->setTransactionalPhp($php);
    $check = $test->getTransactionalPhp();
    $this->assertSame($php, $check, 'Transactional PHP not properly set.');

    $test->setTransactionalPhpIndexer($indexer);
    $check = $test->getTransactionalPhpIndexer();
    $this->assertSame($indexer, $check, 'Transactional PHP indexer not properly set.');
  }

}
