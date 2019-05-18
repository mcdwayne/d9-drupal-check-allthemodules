<?php

namespace Drupal\Tests\automatic_updates\Kernel\ReadinessChecker;

use Drupal\automatic_updates\ReadinessChecker\ReadOnlyFilesystem;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\Exception\FileWriteException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\KernelTests\KernelTestBase;
use DrupalFinder\DrupalFinder;
use Psr\Log\LoggerInterface;

/**
 * Tests read only readiness checking.
 *
 * @group automatic_updates
 */
class ReadOnlyTest extends KernelTestBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'automatic_updates',
    'system',
  ];

  /**
   * Tests the functionality of read only filesystem readiness checks.
   */
  public function testReadOnly() {
    $messages = $filesystem = $this->container->get('automatic_updates.readonly_checker')->run();
    $this->assertEmpty($messages);

    $filesystem = $this->createMock(FileSystemInterface::class);
    $filesystem->expects($this->any())
      ->method('copy')
      ->withAnyParameters()
      ->will($this->onConsecutiveCalls(
        $this->throwException(new FileWriteException('core.api.php')),
        $this->throwException(new FileWriteException('core.api.php')),
        $this->throwException(new FileWriteException('composer/LICENSE')),
        'full/file/path',
        'full/file/path'
      )
    );
    $filesystem->expects($this->any())
      ->method('delete')
      ->withAnyParameters()
      ->will($this->onConsecutiveCalls(
        $this->throwException(new FileException('delete failed.')),
        $this->throwException(new FileException('delete failed.'))
      )
    );

    $readonly = $this->getMockBuilder(TestReadOnlyFilesystem::class)
      ->setConstructorArgs([
        $this->createMock(LoggerInterface::class),
        new DrupalFinder(),
        $filesystem,
      ])
      ->setMethods([
        'areSameLogicalDisk',
        'exists',
      ])
      ->getMock();
    $readonly->expects($this->any())
      ->method('areSameLogicalDisk')
      ->withAnyParameters()
      ->will($this->onConsecutiveCalls(
        TRUE,
        FALSE,
        FALSE
      )
    );
    $readonly->expects($this->any())
      ->method('exists')
      ->withAnyParameters()
      ->will($this->onConsecutiveCalls(
        FALSE,
        TRUE,
        TRUE,
        TRUE
      )
    );

    // Test can't locate drupal.
    $messages = $readonly->run();
    $this->assertEquals([$this->t('The web root could not be located.')], $messages);

    // Test same logical disk.
    $expected_messages = [];
    $expected_messages[] = $this->t('Logical disk at "/var/www" is read only. Updates to Drupal cannot be applied against a read only file system.');
    $messages = $readonly->run();
    $this->assertEquals($expected_messages, $messages);

    // Test read-only.
    $expected_messages = [];
    $expected_messages[] = $this->t('Drupal core filesystem at "/var/www/core" is read only. Updates to Drupal core cannot be applied against a read only file system.');
    $expected_messages[] = $this->t('Vendor filesystem at "/var/www/vendor" is read only. Updates to the site\'s code base cannot be applied against a read only file system.');
    $messages = $readonly->run();
    $this->assertEquals($expected_messages, $messages);

    // Test delete fails.
    $expected_messages = [];
    $expected_messages[] = $this->t('Drupal core filesystem at "/var/www/core" is read only. Updates to Drupal core cannot be applied against a read only file system.');
    $expected_messages[] = $this->t('Vendor filesystem at "/var/www/vendor" is read only. Updates to the site\'s code base cannot be applied against a read only file system.');
    $messages = $readonly->run();
    $this->assertEquals($expected_messages, $messages);
  }

}

/**
 * Class TestReadOnlyFilesystem.
 */
class TestReadOnlyFilesystem extends ReadOnlyFilesystem {

  /**
   * {@inheritdoc}
   */
  protected $rootPath = '/var/www';

  /**
   * {@inheritdoc}
   */
  protected $vendorPath = '/var/www/vendor';

}
