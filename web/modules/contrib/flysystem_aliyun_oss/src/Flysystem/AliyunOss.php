<?php

namespace Drupal\flysystem_aliyun_oss\Flysystem;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\flysystem\Plugin\FlysystemPluginInterface;
use Drupal\flysystem\Plugin\FlysystemUrlTrait;
use Drupal\flysystem\Plugin\ImageStyleGenerationTrait;
use Drupal\flysystem_aliyun_oss\Flysystem\Adapter\AliyunOssAdapter;
use League\Flysystem\Config;
use OSS\OssClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drupal plugin for the "Aliyun OSS" Flysystem adapter.
 *
 * @Adapter(id = "aliyun_oss")
 */
class AliyunOss implements FlysystemPluginInterface, ContainerFactoryPluginInterface {

  use ImageStyleGenerationTrait;
  use FlysystemUrlTrait {
    getExternalUrl as getDownloadUrl;
  }

  /**
   * The Oss client.
   *
   * @var \OSS\OssClient
   */
  private $client;

  /**
   * Plugin config.
   *
   * @var \League\Flysystem\Config
   */
  protected $config;

  /**
   * The bucket name.
   *
   * @var string
   */
  private $bucket;

  /**
   * The prefix.
   *
   * @var string
   */
  private $prefix;

  /**
   * The endpoint.
   *
   * @var string
   */
  private $endpoint;

  /**
   * The url expire time.
   *
   * @var string
   */
  private $expire;

  /**
   * AliyunOss constructor.
   *
   * @param \OSS\OssClient $client
   *   The Oss Client.
   * @param \League\Flysystem\Config $config
   *   The configuration.
   */
  public function __construct(OssClient $client, Config $config) {

    $this->client = $client;
    $this->config = $config;
    $this->bucket = $config->get('bucket', '');
    $this->endpoint = $config->get('endpoint', '');
    $this->prefix = $config->get('prefix', '');
    $this->expire = $config->get('expire', 3600);
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   *
   * @throws \Exception
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // TODO: timeout: 3600, connectTimeout: 10.
    $access_key_id = $configuration['access_key_id'];
    $access_key_secret = $configuration['access_key_secret'];
    $endpoint = $configuration['endpoint'];

    unset($configuration['access_key_id'], $configuration['access_key_secret']);

    $config = new Config($configuration);

    $client = new OssClient($access_key_id, $access_key_secret, $endpoint);

    $useSSL = $config->get('use_https', FALSE);

    $timeout = $config->get('timeout', 3600);

    $connect_timeout = $config->get('connect_timeout', 10);

    $client->setUseSSL($useSSL);

    $client->setTimeout($timeout);

    $client->setConnectTimeout($connect_timeout);

    return new static($client, $config);

  }

  /**
   * Returns the AliyunOss Flysystem adapter.
   *
   * Plugins should not keep references to the adapter. If a plugin needs to
   * perform filesystem operations, it should either use a scheme:// or have the
   * \Drupal\flysystem\FlysystemFactory injected.
   *
   * @return \League\Flysystem\AdapterInterface
   *   The Flysytem adapter.
   *
   * @throws \Exception
   */
  public function getAdapter() {
    return new AliyunOssAdapter($this->client, $this->bucket, $this->config, $this->prefix);
  }

  /**
   * @inheritdoc
   *
   * @throws \OSS\Core\OssException
   */
  public function ensure($force = FALSE) {
    if (!$this->client->doesBucketExist($this->bucket)) {
      return [
        [
          'severity' => RfcLogLevel::ERROR,
          'message' => 'Bucket %bucket does not exist.',
          'context' => [
            '%bucket' => $this->bucket,
          ],
        ],
      ];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \OSS\Core\OssException
   */
  public function getExternalUrl($uri) {

    $target = $this->getTarget($uri);

    if (strpos($target, 'styles/') === 0 && !file_exists($uri)) {
      $this->generateImageStyle($target);
    }

    $url = '';
    if ($this->config->get('visibility') === 'public') {
      $meta = $this->client->getObjectMeta($this->bucket, UrlHelper::encodePath($target));
      $url = $meta['info']['url'];
    }
    else {
      $url = $this->client->signUrl($this->bucket, UrlHelper::encodePath($target), $this->expire);
    }

    $useSSL = $this->config->get('use_https', FALSE);
    $schema = $useSSL ? 'https://' : 'http://';

    $url_prefix = $schema . $this->bucket . '.' . $this->endpoint;

    if (strpos($url, $url_prefix) === 0) {
      $relative_path = substr($url, strlen($url_prefix));
      $cname = $this->config->get('cname', '');
      if (!empty($cname)) {
        return $schema . $cname . $relative_path;
      }
    }
    return $url;
  }

}
