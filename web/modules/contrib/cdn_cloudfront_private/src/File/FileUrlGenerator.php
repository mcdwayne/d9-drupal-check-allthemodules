<?php

namespace Drupal\cdn_cloudfront_private\File;

use Aws\CloudFront\CloudFrontClient;
use Drupal\cdn_cloudfront_private\CdnCloudfrontPrivateEvents;
use Drupal\cdn_cloudfront_private\Event\CdnCloudfrontPrivateEvent;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\key\KeyRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class FileUrlGenerator.
 */
class FileUrlGenerator {

  /**
   * The file generator; there is no interface, so typehinting the class
   * directly, here.
   *
   * @var \Drupal\cdn\File\FileUrlGenerator
   */
  protected $decoratedGenerator;

  /**
   * The Cloudfront client.
   *
   * @var \Aws\CloudFront\CloudFrontClient
   */
  protected $client;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The key repository.
   *
   * @var \Drupal\key\KeyRepository
   */
  protected $keyRepository;

  /**
   * The page cache kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * Constructor.
   *
   * @param object $decoratedGenerator
   *   The decorated generator. Right now there is no interface, but we don't
   *   hint a particular class because there may be more than one decorator.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The config factory.
   * @param \Drupal\key\KeyRepository $keyRepository
   *   The key repository.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $killSwitch
   *   The page cache kill switch.
   */
  public function __construct($decoratedGenerator, EventDispatcherInterface $eventDispatcher, ConfigFactory $configFactory, KeyRepository $keyRepository, KillSwitch $killSwitch) {
    $this->decoratedGenerator = $decoratedGenerator;
    $this->eventDispatcher = $eventDispatcher;
    $this->configFactory = $configFactory;
    $this->keyRepository = $keyRepository;
    $this->killSwitch = $killSwitch;
  }

  /**
   * Magic method to proxy calls for non-overridden methods.
   */
  public function __call($method, $args) {
    return call_user_func_array(array($this->decoratedGenerator, $method), $args);
  }

  /**
   * Get the Cloudfront client.
   *
   * @return \Aws\CloudFront\CloudFrontClient
   *   Client.
   */
  protected function getClient() {
    if ($this->client) {
      return $this->client;
    }
    $this->client = new CloudFrontClient([
    // Not effective, but required.
      'region' => 'us-west-2',
      'version' => '2016-09-29',
    ]);
    return $this->client;
  }

  /**
   * Returns a default policy statement.
   *
   * @return array
   *   A default policy statement.
   */
  public static function getDefaultPolicyStatement() {
    return [
      'Resource' => 'https://*',
      'Condition' => [
        'DateLessThan' => [
          'AWS:EpochTime' => REQUEST_TIME + (60 * 60 * 5),
        ],
      ],
    ];
  }

  /**
   * Sign a URL, or generate signed cookies for the policy.
   *
   * @param array $policy
   *   The policy statement.
   * @param string $method
   *   Either cookie or url.
   * @param string $url
   *   The URL (optional if signing a cookie.)
   * @param bool $secure
   *   Whether to mark the cookie as secure.
   * @param string $path
   *   Path to apply cookie.
   *
   * @return string
   *   The URL, signed.
   */
  public function getSignedUrl(array $policy, $method, $url = NULL, $secure = TRUE, $path = '/') {
    if ($method == 'url' && is_null($url)) {
      throw new \Exception('Must specify a url if signing a URL.');
    }
    $client = $this->getClient();
    $config = $this->configFactory->get('cdn_cloudfront_private.config');
    $pem = $this->keyRepository->getKey($config->get('key'));
    if ($pem->getKeyProvider()->getPluginDefinition()['storage_method'] == 'file') {
      $configuration = $pem->getKeyProvider()->getConfiguration();
      $privateKey = $configuration['file_location'];
    }
    else {
      $privateKey = $pem->getKeyValue();
    }
    $opts = [
      'private_key' => $privateKey,
      'key_pair_id' => $config->get('key_pair_id'),
      'url' => $url,
      'policy' => json_encode(['Statement' => [$policy]], JSON_UNESCAPED_SLASHES),
    ];
    if ($method == 'cookie') {
      if (empty($policy['Resource'])) {
        throw new \Exception('AWS signed cookies require a resource to be specified.');
      }
      $cookies = $client->getSignedCookie($opts);
      $domain = $config->get('domain');
      foreach ($cookies as $c => $d) {
        setrawcookie($c, $d, NULL, $path, $domain, $secure, TRUE);
      }
    }
    else {
      $url = $client->getSignedUrl($opts);
    }
    return $url;
  }

  /**
   * Generates a CDN file URL for local files that are mapped to a CDN.
   *
   * Compatibility: normal paths and stream wrappers.
   *
   * There are two kinds of local files:
   * - "managed files", i.e. those stored by a Drupal-compatible stream wrapper.
   *   These are files that have either been uploaded by users or were generated
   *   automatically (for example through CSS aggregation).
   * - "shipped files", i.e. those outside of the files directory, which ship as
   *   part of Drupal core or contributed modules or themes.
   *
   * @param string $uri
   *   The URI to a file for which we need a CDN URL, or the path to a shipped
   *   file.
   *
   * @return string|false
   *   A string containing the protocol-relative CDN file URI, or FALSE if this
   *   file URI should not be served from a CDN.
   */
  public function generate($uri) {
    $originalUri = $uri;
    // Early return on FALSE return.
    if (!$uri = $this->decoratedGenerator->generate($uri)) {
      return FALSE;
    }
    $event = new CdnCloudfrontPrivateEvent($uri, $originalUri);
    $event->setPolicyStatement(self::getDefaultPolicyStatement());
    $this->eventDispatcher->dispatch(CdnCloudfrontPrivateEvents::DETERMINE_URI_PROTECTION, $event);

    if (!$event->isProtected()) {
      return $uri;
    }
    if (!UrlHelper::isExternal($event->getUri())) {
      throw new \Exception('Cannot sign a non-external URL for Cloudfront.');
    }

    // Signed URLs are likely not cacheable (if they are targeted for a user)
    // so while this is a blunt instrument, there's currently no cache metadata
    // for the generated file URL.
    // @see https://github.com/BradJonesLLC/cdn_cloudfront_private/issues/1
    if (!$event->isPageCacheable()) {
      $this->killSwitch->trigger();
    }

    $signableUri = strpos($event->getUri(), '//') === 0
      ? 'https:' . $event->getUri()
      : $event->getUri();
    return $event->needsProcessing()
      ? $this->getSignedUrl($event->getPolicyStatement(), $event->getMethod(), $signableUri)
      : $event->getUri();
  }

}
