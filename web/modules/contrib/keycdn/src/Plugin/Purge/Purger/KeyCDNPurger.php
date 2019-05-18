<?php

namespace Drupal\keycdn\Plugin\Purge\Purger;

use Drupal\Core\Config\ConfigFactory;
use Drupal\keycdn\Entity\KeyCDNPurgerSettings;
use Drupal\keycdn\EventSubscriber\KeycdnCacheTagHeaderGenerator;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Purger\PurgerBase;
use Drupal\purge\Plugin\Purge\Purger\PurgerInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * KeyCDNPurger Purger implementation.
 *
 * @PurgePurger(
 *   id = "purge_purger_keycdn",
 *   label = @Translation("Key CDN Purger"),
 *   configform = "\Drupal\keycdn\Form\KeyCDNPurgerConfigForm",
 *   cooldown_time = 1.0,
 *   description = @Translation("Invalidates the Key CDN cache."),
 *   multi_instance = TRUE,
 *   types = {"tag", "url", "everything"},
 * )
 */
class KeyCDNPurger extends PurgerBase implements PurgerInterface {

  /**
   * Configuration factory
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $factory;

  /**
   * The settings entity holding all configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('config.factory')
    );
  }

  /**
   * Constructs an instance of KeyCDNPurger.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The http client.
   * @param \Drupal\Core\Config\ConfigFactory $factory
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $http_client, ConfigFactory $factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->settings = $factory->get('keycdn.settings.' . $this->getId());
    $this->factory = $factory;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    if ($this->settings->get('name')) {
      return $this->settings->get('name');
    }
    else {
      return parent::getLabel();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIdealConditionsLimit() {
    $purgers = $this->factory->get('purge.plugins')->get('purgers');
    $keycdn_purgers = array_filter($purgers, function ($purger) {
      /** @var \Drupal\purge\Plugin\Purge\Purger\PurgerInterface $purger */
      return ($purger['plugin_id'] == 'purge_purger_keycdn');
    });
    $keycdn_purger_count = count($keycdn_purgers);
    // KeyCDN allows 20 requests per minute. Divide that among the purgers.
    $reqs_per_purger = floor(20 / $keycdn_purger_count);
    // KeyCDN allows 32 tags per request.
    $limit = 32 * $reqs_per_purger;
    return (int) $limit;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate(array $invalidations) {
    throw new \LogicException('You should not be here.');
  }

  /**
   * Invalidate a set of urls.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface[] $invalidations
   *   The invalidator instance.
   *
   * @throws \Exception
   */
  public function invalidateUrls(array $invalidations) {
    $urls = [];
    // Set all invalidation states to PROCESSING before kick off purging.
    /* @var \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface $invalidation */
    foreach ($invalidations as $invalidation) {
      $invalidation->setState(InvalidationInterface::PROCESSING);
      try {
        $url = $invalidation->getUrl()->getInternalPath();
      }
      catch (\UnexpectedValueException $e) {
        $url = $this->normalizePath($invalidation->getUrl()->getUri());
      }
      $urls[] = $url;
    }

    if (empty($urls)) {
      foreach ($invalidations as $invalidation) {
        $invalidation->setState(InvalidationInterface::FAILED);
        throw new \Exception('No url found to purge');
      }
    }

    // Invalidate and update the item state.
    $invalidation_state = $this->invalidateItems('urls', $urls);
    $this->updateState($invalidations, $invalidation_state);
  }

  /**
   * Invalidate a set of tags.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface[] $invalidations
   *   The invalidator instance.
   *
   * @throws \Exception
   */
  public function invalidateTags(array $invalidations) {
    $tags = [];
    // Set all invalidation states to PROCESSING before kick off purging.
    /* @var \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface $invalidation */
    foreach ($invalidations as $invalidation) {
      $invalidation->setState(InvalidationInterface::PROCESSING);
      $tags[] = $invalidation->getExpression();
    }

    if (empty($tags)) {
      foreach ($invalidations as $invalidation) {
        $invalidation->setState(InvalidationInterface::FAILED);
        throw new \Exception('No tag found to purge');
      }
    }

    // Invalidate and update the item state.
    $hashes = KeycdnCacheTagHeaderGenerator::cacheTagsToHashes($tags);
    // KeyCDN has 128 char limit https://www.keycdn.com/api#purge-zone-tag
    $hash_sets = array_chunk($hashes, 32);
    foreach ($hash_sets as $hash_set) {
      $invalidation_state = $this->invalidateItems('tags', $hash_set);
    }
    $this->updateState($invalidations, $invalidation_state);
  }

  /**
   * Invalidate everything.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface[] $invalidations
   *   The invalidator instance.
   */
  public function invalidateAll(array $invalidations) {
    $this->updateState($invalidations, InvalidationInterface::PROCESSING);
    // Invalidate and update the item state.
    $invalidation_state = $this->invalidateItems();
    $this->updateState($invalidations, $invalidation_state);
  }

  /**
   * {@inheritdoc}
   */
  public function routeTypeToMethod($type) {
    $methods = [
      'tag'  => 'invalidateTags',
      'url'  => 'invalidateUrls',
      'everything' => 'invalidateAll',
    ];
    return isset($methods[$type]) ? $methods[$type] : 'invalidate';
  }

  /**
   * {@inheritdoc}
   */
  public function hasRuntimeMeasurement() {
    return TRUE;
  }

  /**
   * Invalidate Key CDN cache.
   *
   * @param mixed $type
   *   Type to purge like tags/url. If null, will purge everything.
   * @param string[] $invalidates
   *   A list of items to invalidate.
   *
   * @return int
   *    Returns invalidate items.
   */
  protected function invalidateItems($type = NULL, array $invalidates = []) {
    // Get zone info.
    $zone = $this->settings->get('zone');
    $uri = $this->getPurgeUri($type) . "/{$zone}.json";

    // This will contain the info that need to be cleared. For the case of
    // 'everything', this will be empty and thus nothing.
    $params = [];

    // If url/tag.
    if ($type) {
      $params['json'] = [$type => $invalidates];
    }

    try {
      // Purge everything for the given zone.
      /** @var \Psr\Http\Message\ResponseInterface $response */
      $response = $this->httpClient->request('DELETE', $uri, [
        'auth' => [$this->settings->get('api_key'), ''],
        'headers' => ['Content-Type' => 'application/json'],
        'connect_timeout' => 2,
      ] + $params);

      // If successfully clears cache.
      if ($response->getStatusCode() == 200) {
        $body = json_decode($response->getBody(), TRUE);
        if ($body['status'] == 'success') {
          $this->logger()->debug('KeyCDN purge successful. Uri: %uri, Params: %params',
              ['%uri' => $uri, '%params' => print_r($params, TRUE)]);
          return InvalidationInterface::SUCCEEDED;
        }
        // Some errors come with status 200. https://www.keycdn.com/api#errors
        $this->logger()->error('KeyCDN purge failed. Status code %code: %message',
            ['%code' => $response->getStatusCode(), '%message' => $body['description']]);
        return InvalidationInterface::FAILED;
      }
      // Should never be fired, since HTTP errors throw exceptions.
      $this->logger()->error('KeyCDN purge failed. Status code %code: %message',
        ['%code' => $response->getStatusCode(), '%message' => $response->getReasonPhrase()]);
      return InvalidationInterface::FAILED;
    }
    catch (\Exception $e) {
      // If something bad happens.
      $this->logger()->error('KeyCDN purge request failed. Status code %code: %message',
        ['%code' => $e->getCode(), '%message' => $e->getMessage()]);
      return InvalidationInterface::FAILED;
    }
  }

  /**
   * Update the invalidation state of items.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface[] $invalidations
   *   The invalidator instance.
   * @param int $invalidation_state
   *   The invalidation state.
   */
  protected function updateState(array $invalidations, $invalidation_state) {
    // Update the state.
    foreach ($invalidations as $invalidation) {
      $invalidation->setState($invalidation_state);
    }
  }

  /**
   * Get purge uri.
   *
   * @param string $type
   *   Type of which uri needs to return.
   *
   * @return mixed|string
   *   Uri of the given type.
   */
  protected function getPurgeUri($type = NULL) {
    $uri = [
      'tags' => 'https://api.keycdn.com/zones/purgetag',
      'urls' => 'https://api.keycdn.com/zones/purgeurl',
    ];

    return isset($uri[$type]) ? $uri[$type] : 'https://api.keycdn.com/zones/purge';
  }

  /**
   * Converts any path or URL into a normalized path.
   *
   * @param string $url
   *   URL to normalize.
   *
   * @return string
   *    Returns normalized path.
   */
  public function normalizePath($url) {
    $parsed_url = parse_url($url);
    $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
    $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
    return $path . $query;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    KeyCDNPurgerSettings::load($this->getId())->delete();
  }
  
  public function status() {
    return $this->settings->get('status');
  }

  /**
   * Only return types if the purger is enabled. To disable a purger, use
   * configuration overrides (e.g. environment specific purgers.). See
   * https://www.drupal.org/docs/8/api/configuration-api/configuration-override-system
   */
  public function getTypes() {
    return $this->status() ? parent::getTypes() : [];
  }
}
