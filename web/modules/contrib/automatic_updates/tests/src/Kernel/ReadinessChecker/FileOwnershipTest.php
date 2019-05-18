<?php

namespace Drupal\Tests\automatic_updates\Kernel\ReadinessChecker;

use Drupal\automatic_updates\ReadinessChecker\FileOwnership;
use Drupal\KernelTests\KernelTestBase;
use org\bovigo\vfs\vfsStream;

/**
 * Tests modified code readiness checking.
 *
 * @group automatic_updates
 */
class FileOwnershipTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'automatic_updates',
  ];

  /**
   * Tests the functionality of modified code readiness checks.
   */
  public function testFileOwnership() {
    // No ownership problems.
    $file_ownership = new FileOwnership($this->container->get('automatic_updates.drupal_finder'));
    $messages = $file_ownership->run();
    $this->assertEmpty($messages);

    // Ownership problems.
    $file_ownership = new TestFileOwnership($this->container->get('automatic_updates.drupal_finder'));
    $messages = $file_ownership->run();
    $this->assertCount(1, $messages);
    $this->assertStringStartsWith('Files are owned by uid "23"', (string) $messages[0]);
    $this->assertStringEndsWith('The file owner and PHP user should be the same during an update.', (string) $messages[0]);
  }

}

/**
 * Class TestFileOwnership.
 */
class TestFileOwnership extends FileOwnership {

  /**
   * {@inheritdoc}
   */
  protected function doCheck() {
    $file_stream = vfsStream::setup('core', '755', ['core.api.php' => 'contents']);
    $file = $file_stream->getChild('core.api.php');
    $file->chown(23)->chgrp(23);
    return $this->ownerIsScriptUser($file->url());
  }

}
