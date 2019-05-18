<?php

namespace Drupal\Tests\ossfs\Kernel;

use Drupal\Component\Utility\UrlHelper;
use Drupal\KernelTests\KernelTestBase;
use OSS\OssClient;

class OssfsRemoteTestBase extends KernelTestBase {

  /**
   * Modules to installs.
   *
   * @var array
   */
  protected static $modules = [
    'ossfs',
  ];

  /**
   * The ossfs config.
   *
   * @var array
   */
  protected $ossfsConfig;

  /**
   * The ossfs database storage.
   *
   * @var \Drupal\ossfs\OssfsStorageInterface
   */
  protected $storage;

  /**
   * The OSS client.
   *
   * @var \OSS\OssClient
   */
  protected $client;

  /**
   * The object uris to be clean up in OSS.
   *
   * @var array
   */
  protected $cleanup;

  /**
   * {@inheritdoc}
   */
  protected function setup() {
    parent::setUp();
    $this->installSchema('ossfs', 'ossfs_file');

    if (!getenv('OSS_ACCESS_KEY_ID')) {
      throw new \Exception('Please config the OSS environment variables in phpunit.xml');
    }
    $config = [
      'access_key' => getenv('OSS_ACCESS_KEY_ID'),
      'secret_key' => getenv('OSS_ACCESS_KEY_SECRET'),
      'bucket' => getenv('OSS_BUCKET'),
      'region' => getenv('OSS_REGION'),
      'cname' => getenv('OSS_CNAME'),
      'prefix' => getenv('OSS_PREFIX'),
      'internal' => FALSE,
    ];
    $this->config('ossfs.settings')->setData($config)->save();
    $this->ossfsConfig = $config;

    $this->storage = $this->container->get('ossfs.storage');

    $endpoint = $config['region'] . ($config['internal'] ? '-internal' : '') . '.aliyuncs.com';
    $this->client = new OssClient($config['access_key'], $config['secret_key'], $endpoint, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    if ($this->cleanup) {
      $objects = array_map(function ($uri) {
        return $this->getKey($uri);
      }, $this->cleanup);
      $this->cleanup = NULL;

      $this->client->deleteObjects($this->ossfsConfig['bucket'], $objects);

    }
    parent::tearDown();
  }

  /**
   * Returns the OSS key for the given uri.
   *
   * @param $uri
   *   The uri.
   *
   * @return string
   */
  protected function getKey($uri) {
    $prefix = (string) $this->ossfsConfig['prefix'];
    $prefix = $prefix === '' ? '' : UrlHelper::encodePath($prefix) . '/';
    list(, $target) = explode('://', $uri, 2);

    return $prefix . UrlHelper::encodePath($target);
  }

  /**
   * Normalizes object metadata returned from OSS into a ossfs metadata.
   *
   * Remove 'changed' value for tests.
   *
   * @param string $uri
   *   The uri of the resource (headObject or getObject).
   * @param array $response
   *   (optional) An array of metadata values for the object in OSS. If NULL or
   *   omitted, a directory metadata is returned.
   *
   * @return array
   *   An array of metadata values.
   */
  protected function normalizeResponse($uri, array $response = NULL) {
    $data = [
      'uri' => $uri,
      'type' => '',
      'filemime' => '',
      'filesize' => 0,
      'imagesize' => '',
      // 'changed' => REQUEST_TIME,
    ];

    if (isset($response)) {
      $data['type'] = 'file';
      $data['filemime'] = $response['content-type'];
      $data['filesize'] = (int) $response['content-length'];
      // $data['changed'] = strtotime($response['last-modified']);
    }
    else {
      $data['type'] = 'dir';
    }
    return $data;
  }

  /**
   * Normalizes metadata from local storage.
   *
   * Remove 'changed' value for tests.
   *
   * @param array $data
   *   The metadata.
   *
   * @return array
   */
  protected function normalizeStorage(array $data) {
    $data['filesize'] = (int) $data['filesize'];
    unset($data['changed']);
    return $data;
  }

}
