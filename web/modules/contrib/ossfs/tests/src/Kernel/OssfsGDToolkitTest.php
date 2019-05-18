<?php

namespace Drupal\Tests\ossfs\Kernel;

/**
 * @group ossfs
 */
class OssfsGDToolkitTest extends OssfsRemoteTestBase {

  use StorageTrait;

  /**
   * Modules to installs.
   *
   * @var array
   */
  protected static $modules = [
    'system',
  ];

  /**
   * The image toolkit plugin manager.
   *
   * @var \Drupal\Core\ImageToolkit\ImageToolkitManager
   */
  protected $toolkitManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  protected function setup() {
    parent::setUp();
    $this->toolkitManager = $this->container->get('image.toolkit.manager');;
    $this->connection = $this->container->get('database');
  }

  /**
   * Tests the gd toolkit provided by this module is enabled in favor of the
   * system default gd toolkit.
   */
  public function testOssfsGDToolkitEnabled() {
    $definitions = $this->toolkitManager->getDefinitions();
    $this->assertCount(1, $definitions);
    $definition = $definitions['gd'];
    $this->assertEquals('Drupal\ossfs\Plugin\ImageToolkit\OssfsGDToolkit', $definition['class']);
  }

  /**
   * Tests the imagesize data is from the local storage rather than the result
   * of getimagesize().
   */
  public function testParseFileWithDataFromStorage() {
    $uri = 'oss://abc.jpg';
    $this->insertRecord($uri, 'file', '100,200,' . IMAGETYPE_JPEG);

    /** @var \Drupal\Core\ImageToolkit\ImageToolkitInterface $toolkit */
    $toolkit = $this->toolkitManager->createInstance('gd');
    $toolkit->setSource($uri);
    $result = $toolkit->parseFile();
    $this->assertTrue($result);
    $this->assertEquals('100', $toolkit->getWidth());
    $this->assertEquals('200', $toolkit->getHeight());
    $this->assertEquals('image/jpeg', $toolkit->getMimeType());
  }

}
