<?php

namespace Drupal\Tests\filecache\Kernel;

use Drupal\KernelTests\Core\Cache\GenericCacheBackendUnitTestBase;

/**
 * Tests the FileSystemBackend cache backend.
 *
 * @group filecache
 */
class FileSystemBackendTest extends GenericCacheBackendUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'filecache',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['system']);
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installSchema('file', ['file_usage']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createCacheBackend($bin) {
    // Set the FileSystemBackend as the default cache backend.
    $this->setSetting('cache', ['default' => 'cache.backend.file_system']);

    $base_path = file_default_scheme() . '://filecache';
    $cache_path_settings = [
      'directory' => [
        'default' => $base_path,
        'bins' => [
          'foo' => $base_path . '/foo',
          'bar' => $base_path . '/bar',
        ],
      ],
    ];
    $this->setSetting('filecache', $cache_path_settings);

    return $this->container->get('cache.backend.file_system')->get($bin);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    // The parent method will loop over all cache backends used in the test and
    // call ::deleteAll() on them to get rid of persistent cache data. This
    // doesn't work for us since we are using the virtual file system which is
    // no longer available after the test completes. We don't need to worry
    // about cache data persisting after the test since the virtual file system
    // is cleaned up in KernelTestBase::tearDown().
    $this->cachebackends = [];
    parent::tearDown();
  }

}
