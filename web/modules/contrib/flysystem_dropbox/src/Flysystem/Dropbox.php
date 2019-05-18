<?php

/**
 * @file
 * Contains \Drupal\flysystem_dropbox\Flysystem\Dropbox.
 */

namespace Drupal\flysystem_dropbox\Flysystem;

use Dropbox\Client;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\flysystem\Flysystem\Adapter\MissingAdapter;
use Drupal\flysystem\Plugin\FlysystemPluginInterface;
use Drupal\flysystem\Plugin\FlysystemUrlTrait;
use Drupal\flysystem\Plugin\ImageStyleGenerationTrait;
use GuzzleHttp\Psr7\Uri;
use League\Flysystem\Dropbox\DropboxAdapter;

/**
 * Drupal plugin for the "Dropbox" Flysystem adapter.
 *
 * @Adapter(id = "dropbox")
 */
class Dropbox implements FlysystemPluginInterface {

  use FlysystemUrlTrait {
    getExternalUrl as getDownloadlUrl;
  }

  use ImageStyleGenerationTrait;

  /**
   * The Dropbox client.
   *
   * @var \Dropbox\Client
   */
  protected $client;

  /**
   * The Dropbox client ID.
   *
   * @var string
   */
  protected $clientId;

  /**
   * The path prefix inside the Dropbox folder.
   *
   * @var string
   */
  protected $prefix;

  /**
   * The Dropbox API token.
   *
   * @var string
   */
  protected $token;

  /**
   * Whether to serve files via Dropbox.
   *
   * @var bool
   */
  protected $usePublic;

  /**
   * Constructs a Dropbox object.
   *
   * @param array $configuration
   *   Plugin configuration array.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   */
  public function __construct(array $configuration) {
    $this->prefix = isset($configuration['prefix']) ? $configuration['prefix'] : '';
    $this->token = $configuration['token'];
    $this->clientId = $configuration['client_id'];
    $this->usePublic = !empty($configuration['public']);
  }

  /**
   * {@inheritdoc}
   */
  public function getAdapter() {
    try {
      $adapter = new DropboxAdapter($this->getClient(), $this->prefix);
    }

    catch (\Exception $e) {
      $adapter = new MissingAdapter();
    }

    return $adapter;
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl($uri) {
    if ($this->usePublic) {
      return $this->getPublicUrl($uri);
    }

    return $this->getDownloadlUrl($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function ensure($force = FALSE) {
    try {
      $info = $this->getClient()->getAccountInfo();
    }
    catch (\Exception $e) {
      return [[
        'severity' => RfcLogLevel::ERROR,
        'message' => 'The Dropbox client failed with: %error.',
        'context' => ['%error' => $e->getMessage()],
      ]];
    }

    return [];
  }

  /**
   * Returns the public Dropbox URL.
   *
   * @param string $uri
   *   The file URI.
   *
   * @return string|false
   *   The public URL, or false on failure.
   */
  protected function getPublicUrl($uri) {
    $target = $this->getTarget($uri);

    // Quick exit for existing files.
    if ($link = $this->getSharableLink($target)) {
      return $link;
    }

    // Support image style generation.
    if ($this->generateImageStyle($target)) {
      return $this->getSharableLink($target);
    }

    return FALSE;
  }

  /**
   * Returns the Dropbox sharable link.
   *
   * @param string $target
   *   The file target.
   *
   * @return string|bool
   *   The sharable link, or false on failure.
   */
  protected function getSharableLink($target) {
    try {
      $link = $this->getClient()->createShareableLink('/' . $target);
    }
    catch (\Exception $e) {}

    if (empty($link)) {
      return FALSE;
    }

    $uri = (new Uri($link))->withHost('dl.dropboxusercontent.com');

    return (string) Uri::withoutQueryValue($uri, 'dl');
  }

  /**
   * Returns the Dropbox client.
   *
   * @return \Dropbox\Client
   *   The Dropbox client.
   */
  protected function getClient() {
    if (!isset($this->client)) {
      $this->client = new Client($this->token, $this->clientId);
    }

    return $this->client;
  }

}
