<?php

namespace Drupal\flysystem_aliyun_oss\Flysystem\Adapter;

use OSS\Core\OssException;
use OSS\OssClient;
use League\Flysystem\Util;
use League\Flysystem\Config;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\StreamedTrait;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\AdapterInterface;

/**
 * Aliyun OSS Adapter class.
 */
class AliyunOssAdapter extends AbstractAdapter {

  use StreamedTrait;
  use NotSupportingVisibilityTrait;

  /**
   * Aliyun Oss Client.
   *
   * @var \OSS\OssClient
   */
  protected $client;

  /**
   * Bucket name.
   *
   * @var string
   */
  protected $bucket;

  /**
   * The config.
   *
   * @var \League\Flysystem\Config
   */
  protected $config = [];

  /**
   * The options mapping.
   *
   * @var array
   */
  protected static $mappingOptions = [
    'mimetype' => OssClient::OSS_CONTENT_TYPE,
    'size' => OssClient::OSS_LENGTH,
  ];

  /**
   * AliyunOssAdapter constructor.
   *
   * @param \Oss\OssClient $client
   *   The OSS client.
   * @param string $bucket
   *   The bucket name.
   * @param \League\Flysystem\Config $config
   *   The config.
   * @param string $prefix
   *   The prefix.
   */
  public function __construct(OssClient $client, $bucket, Config $config, $prefix = '') {
    $this->client = $client;
    $this->bucket = $bucket;
    $this->config = $config;
    $this->setPathPrefix($prefix);
  }

  /**
   * Get the Aliyun Oss Client bucket.
   *
   * @return string
   *   The buckut.
   */
  public function getBucket() {
    return $this->bucket;
  }

  /**
   * Get the Aliyun Oss Client instance.
   *
   * @return \OSS\OssClient
   *   the client.
   */
  public function getClient() {
    return $this->client;
  }

  /**
   * @inheritdoc
   */
  public function write($path, $contents, Config $config) {

    $object = $this->applyPathPrefix($path);
    $options = $this->getOptionsFromConfig($config);

    if (!isset($options[OssClient::OSS_LENGTH])) {
      $options[OssClient::OSS_LENGTH] = Util::contentSize($contents);
    }

    if (!isset($options[OssClient::OSS_CONTENT_TYPE])) {
      $options[OssClient::OSS_CONTENT_TYPE] = Util::guessMimeType($path, $contents);
    }

    $this->client->putObject($this->bucket, $object, $contents, $options);

    $type = 'file';

    $result = compact('type', 'path', 'contents');

    $result['mimetype'] = $options[OssClient::OSS_CONTENT_TYPE];

    $result['size'] = $options[OssClient::OSS_LENGTH];

    return $result;
  }

  /**
   * @inheritdoc
   *
   * @throws \OSS\Core\OssException
   */
  public function update($path, $contents, Config $config) {
    if (!$config->has('visibility') && !$config->has('ACL')) {
      $config->set('ACL', $this->getObjectAcl($path));
    }
    return $this->write($path, $contents, $config);
  }

  /**
   * @inheritdoc
   */
  public function rename($path, $newpath) {
    $this->copy($path, $newpath);
    $this->delete($path);
  }

  /**
   * @inheritdoc
   */
  public function copy($path, $newpath) {
    $object = $this->applyPathPrefix($path);
    $newobject = $this->applyPathPrefix($newpath);

    $this->client->copyObject($this->bucket, $object, $this->bucket, $newobject);
  }

  /**
   * @inheritdoc
   */
  public function delete($path) {
    $object = $this->applyPathPrefix($path);

    $this->client->deleteObject($this->bucket, $object);
  }

  /**
   * @inheritdoc
   *
   * @throws \OSS\Core\OssException
   */
  public function deleteDir($dirname) {
    $list = $this->listContents($dirname, TRUE);

    $objects = [];
    foreach ($list as $item) {
      if ($item['type'] === 'file') {
        $objects[] = $this->applyPathPrefix($item['path']);
      }
      else {
        $objects[] = $this->applyPathPrefix($item['path']) . '/';
      }
    }

    $this->client->deleteObjects($this->bucket, $objects);
  }

  /**
   * @inheritdoc
   */
  public function createDir($dirname, Config $config) {

    $object = $this->applyPathPrefix($dirname);

    $options = $this->getOptionsFromConfig($config);

    $this->client->createObjectDir($this->bucket, $object, $options);

    return ['path' => $dirname, 'type' => 'dir'];
  }

  /**
   * @inheritdoc
   *
   * @throws \OSS\Core\OssException
   */
  public function has($path) {
    $object = $this->applyPathPrefix($path);

    if ($this->client->doesObjectExist($this->bucket, $object)) {
      return TRUE;
    }

    return $this->doesDirectoryExist($object);
  }

  /**
   * @inheritdoc
   */
  public function read($path) {
    $object = $this->applyPathPrefix($path);
    $contents = $this->client->getObject($this->bucket, $object);
    return compact('contents', 'path');
  }

  /**
   * @inheritdoc
   * @throws \OSS\Core\OssException
   */
  public function listContents($directory = '', $recursive = FALSE) {
    $directory = $this->applyPathSeparator($directory);
    $directory = $this->applyPathPrefix($directory);

    $delimiter = '/';
    $nextMarker = '';
    $maxkeys = 1000;
    $options = [
      'delimiter' => $delimiter,
      'prefix'    => $directory,
      'max-keys'  => $maxkeys,
      'marker'    => $nextMarker,
    ];

    $objects = $this->client->listObjects($this->bucket, $options);

    $object_list = $objects->getObjectList();

    $prefix_list = $objects->getPrefixList();

    $result = [];

    foreach ($object_list as $object_info) {
      if ($object_info->getSize() === 0 && $directory === $object_info->getKey()) {
        $result[] = [
          'type'      => 'dir',
          'path'      => $this->removePathPrefix(rtrim($object_info->getKey(), '/')),
          'timestamp' => strtotime($object_info->getLastModified()),
        ];
        continue;
      }

      $result[] = [
        'type'      => 'file',
        'path'      => $this->removePathPrefix($object_info->getKey()),
        'timestamp' => strtotime($object_info->getLastModified()),
        'size'      => $object_info->getSize(),
      ];
    }

    foreach ($prefix_list as $prefix_info) {
      if ($recursive) {
        $next = $this->listContents($this->removePathPrefix($prefix_info->getPrefix()), $recursive);
        $result = array_merge($result, $next);
      }
      else {
        $result[] = [
          'type'      => 'dir',
          'path'      => $this->removePathPrefix(rtrim($prefix_info->getPrefix(), '/')),
          'timestamp' => 0,
        ];
      }
    }

    return $result;
  }

  /**
   * @inheritdoc
   *
   * @throws \OSS\Core\OssException
   */
  public function getMetadata($path) {
    $object = $this->applyPathPrefix($path);
    try {
      $result = $this->client->getObjectMeta($this->bucket, $object);
      return [
        'type'      => 'file',
        'dirname'   => Util::dirname($path),
        'path'      => $path,
        'timestamp' => strtotime($result['last-modified']),
        'mimetype'  => $result['content-type'],
        'size'      => $result['content-length'],
        'visibility' => $this->config->get('visibility', AdapterInterface::VISIBILITY_PRIVATE),
      ];
    }
    catch (OssException $exception) {
      return [
        'type' => 'dir',
        'path' => $path,
        'timestamp' => REQUEST_TIME,
        'size' => FALSE,
        'visibility' => $this->config->get('visibility', AdapterInterface::VISIBILITY_PRIVATE),
      ];
    }
  }

  /**
   * @inheritdoc
   *
   * @throws \OSS\Core\OssException
   */
  public function getSize($path) {
    return $this->getMetadata($path);
  }

  /**
   * @inheritdoc
   *
   * @throws \OSS\Core\OssException
   */
  public function getMimetype($path) {
    return $this->getMetadata($path);
  }

  /**
   * @inheritdoc
   *
   * @throws \OSS\Core\OssException
   */
  public function getTimestamp($path) {
    return $this->getMetadata($path);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \OSS\Core\OssException
   */
  public function setVisibility($path, $visibility) {
    $object = $this->applyPathPrefix($path);
    $visibility = $visibility === AdapterInterface::VISIBILITY_PUBLIC ? OssClient::OSS_ACL_TYPE_PUBLIC_READ : OssClient::OSS_ACL_TYPE_PRIVATE;
    $this->client->putObjectAcl($this->bucket, $object, $visibility);
    return compact('object', 'visibility');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \OSS\Core\OssException
   */
  public function getVisibility($path) {
    $bucket = $this->bucket;
    $object = $this->applyPathPrefix($path);
    $result['visibility'] = $this->client->getObjectAcl($bucket, $object);
    return $result;
  }

  /**
   * Get options from the config.
   *
   * @param \League\Flysystem\Config $config
   *   The config.
   *
   * @return array
   *   The options.
   */
  protected function getOptionsFromConfig(Config $config) {
    $options = [];
    foreach (static::$mappingOptions as $option => $ossOption) {
      if (!$config->has($option)) {
        continue;
      }
      $options[$ossOption] = $config->get($option);
    }

    return $options;
  }

  /**
   * Get the acl of object.
   *
   * @param string $path
   *   The path/object to check.
   *
   * @return string
   *   The visibility.
   *
   * @throws OssException
   */
  protected function getObjectAcl($path) {
    $metadata = $this->getVisibility($path);
    return $metadata['visibility'] === AdapterInterface::VISIBILITY_PUBLIC ? OssClient::OSS_ACL_TYPE_PUBLIC_READ : OssClient::OSS_ACL_TYPE_PRIVATE;
  }

  /**
   * Check directory exist of not.
   *
   * @param string $object
   *   The object to check.
   *
   * @return bool
   *   The result.
   *
   * @throws \OSS\Core\OssException
   */
  protected function doesDirectoryExist($object) {
    $bucket = $this->bucket;
    $delimiter = '/';
    $nextMarker = '';
    $maxkeys = 1000;
    $prefix = rtrim($object, '/') . '/';
    $options = [
      'delimiter' => $delimiter,
      'prefix'    => $prefix,
      'max-keys'  => $maxkeys,
      'marker'    => $nextMarker,
    ];

    $objects = $this->client->listObjects($bucket, $options);

    $files = $objects->getObjectList();

    $directories = $objects->getPrefixList();

    return $files || $directories;
  }

  /**
   * Add a path separator.
   *
   * @param string $path
   *   The path.
   *
   * @return string
   *   The path with separator.
   */
  protected function applyPathSeparator($path) {
    return rtrim($path, '\\/') . '/';
  }

}
