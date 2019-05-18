<?php

namespace Drupal\bynder;

use Bynder\Api\BynderApiFactory;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Bynder API service.
 *
 * @method array getBrands()
 * @method array getMediaList(array $query)
 * @method array getMediaInfo(string $media_id, int $versions)
 * @method array getMetaproperties(array $query)
 * @method array getTags()
 * @method array uploadFileAsync(array $data)
 * @method array getDerivatives()
 *
 * @package Drupal\bynder
 */
class BynderApi implements BynderApiInterface {

  /**
   * The Bynder integration ID.
   */
  const BYNDER_INTEGRATION_ID = 'a7129512-c6e3-47a3-be40-9a66503e82ed';

  /**
   * Cache ID used to store the tags information.
   */
  const CID_TAGS = 'bynder_tags';

  /**
   * Cache ID used to store the metaproperties information.
   */
  const CID_METAPROPERTIES = 'bynder_metaproperties';

  /**
   * Cache ID used to store the derivatives information.
   */
  const CID_DERIVATIVES = 'bynder_derivatives';

  /**
   * List of API calls that should be cache with their cache keys as values.
   */
  const CACHED_CALLS = [
    'getMetaproperties' => self::CID_METAPROPERTIES,
    'getDerivatives' => self::CID_DERIVATIVES,
  ];

  /**
   * List of getTags queries that are automatically udpated in crons.
   */
  const AUTO_UPDATED_TAGS_QUERIES = [
    [],
    [
      'limit' => 200,
      'orderBy' => 'mediaCount desc',
      'minCount' => 1,
    ],
  ];

  /**
   * Bynder Api instance.
   *
   * @var \Bynder\Api\BynderApiFactory
   */
  protected $bynderApi;

  /**
   * Bynder configuration.
   *
   * Contains array with keys consumer_key, consumer_secret, token, token_secret
   * and account_domain.
   *
   * @var array
   */
  protected $bynderConfig;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * The active session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface $time
   */
  protected $time;

  /**
   * BynderApi constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger factory.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    SessionInterface $session,
    StateInterface $state,
    CacheBackendInterface $cache,
    TimeInterface $time
  ) {
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
    $this->session = $session;
    $this->state = $state;
    $this->cache = $cache;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function initiateOAuthTokenRetrieval() {
    $bynder_settings = $this->configFactory->get('bynder.settings');
    $bynder_configuration = [
      'consumerKey' => $bynder_settings->get('consumer_key'),
      'consumerSecret' => $bynder_settings->get('consumer_secret'),
      'baseUrl' => $bynder_settings->get('account_domain'),
    ];

    $this->bynderApi = BynderApiFactory::create($bynder_configuration);

    $session_data = $this->session->get('bynder', []);
    foreach (explode('&', $this->bynderApi->getRequestToken()->wait()) as $item) {
      $value = explode('=', $item);
      $session_data['request_token'][$value[0]] = $value[1];
    }
    $this->session->set('bynder', $session_data);

    $callback = Url::fromRoute('bynder.oauth', [], ['absolute' => TRUE])
      ->toString(TRUE)
      ->getGeneratedUrl();

    $query = [
      'oauth_token' => $session_data['request_token']['oauth_token'],
      'callback' => $callback,
    ];

    return Url::fromUri($bynder_settings->get('account_domain') .  '/api/v4/oauth/authorise/',
      array(
        'query' => $query,
        'auth' => null,
        'allow_redirects' => false
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function hasAccessToken() {
    $session_data = $this->session->get('bynder', []);

    // Required tokens need to be stored in the session.
    if (empty($session_data['access_token']['oauth_token']) || empty($session_data['access_token']['oauth_token_secret'])) {
      return FALSE;
    }

    // In case of the global config change all user sessions need to expire.
    if (empty($session_data['config_hash']) || $session_data['config_hash'] != $this->state->get('bynder_config_hash')) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function finishOAuthTokenRetrieval(Request $request) {
    $bynder_settings = $this->configFactory->get('bynder.settings');
    $session_data = $this->session->get('bynder', []);
    $bynder_configuration = [
      'consumerKey' => $bynder_settings->get('consumer_key'),
      'consumerSecret' => $bynder_settings->get('consumer_secret'),
      'token' => $request->query->get('oauth_token'),
      'tokenSecret' => $session_data['request_token']['oauth_token_secret'],
      'baseUrl' => $bynder_settings->get('account_domain'),
    ];

    // @TODO Statically cache API object?
    $this->bynderApi = BynderApiFactory::create($bynder_configuration);

    foreach ($this->bynderApi->getAccessToken()->wait() as $key => $item) {
      $session_data['access_token'][$key] = $item;
    }
    unset($session_data['request_token']);
    $session_data['config_hash'] = $this->state->get('bynder_config_hash');
    $this->session->set('bynder', $session_data);
  }

  /**
   * {@inheritdoc}
   */
  public function hasUploadPermissions() {
    $this->getAssetBankManager();
    $user = $this->bynderApi->getCurrentUser()->wait();
    if (isset($user)) {
      $profileId = $user['profileId'];
      $userProfile = $this->bynderApi->getSecurityProfile($profileId)->wait();
      foreach ($userProfile['roles'] as $role) {
        if ($role == 'MEDIAUPLOAD' || $role == 'MEDIAUPLOADFORAPPROVAL') {
          return $role;
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getAssetBankManager() {
    if ($this->bynderConfig) {
      $bynder_configuration = [
        'consumerKey' => $this->bynderConfig['consumerKey'],
        'consumerSecret' => $this->bynderConfig['consumerSecret'],
        'token' => $this->bynderConfig['token'],
        'tokenSecret' => $this->bynderConfig['tokenSecret'],
        'baseUrl' => $this->bynderConfig['baseUrl'],
      ];
    }
    else {
      $bynder_settings = $this->configFactory->get('bynder.settings');
      $bynder_configuration = [
        'consumerKey' => $bynder_settings->get('consumer_key'),
        'consumerSecret' => $bynder_settings->get('consumer_secret'),
        'token' => $bynder_settings->get('token'),
        'tokenSecret' => $bynder_settings->get('token_secret'),
        'baseUrl' => $bynder_settings->get('account_domain'),
      ];

      // @TODO Re-evaluate where global and where user token is used.
      $session_data = \Drupal::service('session')->get('bynder', []);
      if (!empty($session_data['access_token']) && !empty($session_data['config_hash']) && $session_data['config_hash'] == $this->state->get('bynder_config_hash')) {
        $bynder_configuration['token'] = $session_data['access_token']['oauth_token'];
        $bynder_configuration['tokenSecret'] = $session_data['access_token']['oauth_token_secret'];
      }
    }
    $this->bynderApi = BynderApiFactory::create($bynder_configuration);

    return $this->bynderApi->getAssetBankManager();
  }

  /**
   * {@inheritdoc}
   */
  public function setBynderConfiguration(array $config) {
    $this->bynderConfig = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function updateCachedData() {
    $expire = $this->configFactory->get('bynder.settings')
        ->get('cache_lifetime') + $this->time->getRequestTime();
    $items = [];
    foreach (self::CACHED_CALLS as $method => $cid) {
      if ($this->configFactory->get('bynder.settings')->get('debug')) {
        $this->loggerFactory->get('bynder')->debug('Update cache: updating cached data for %method.', ['%method' => $method]);
      }

      $items[$cid] = [
        'data' => call_user_func_array([$this->getAssetBankManager(), $method], [])->wait(),
        'expire' => $expire,
      ];
    }

    foreach (static::AUTO_UPDATED_TAGS_QUERIES as $query) {
      $items[self::CID_TAGS . '_' . md5(implode($query))] = [
        'data' => $this->getAssetBankManager()->getTags($query)->wait(),
        'expire' => $expire,
      ];
    }

    $this->cache->setMultiple($items);
    $this->state->set('bynder_cache_last_update', $this->time->getRequestTime());
  }

  /**
   * Wraps getTags() and makes sure results are cached properly.
   */
  public function getTags($query = []) {
    $bynder_configuration = $this->configFactory->get('bynder.settings');
    if ($bynder_configuration->get('debug')) {
      $this->loggerFactory->get('bynder')->debug('Method: %method is called with arguments: @args', [
        '%method' => 'getTags',
        '@args' => print_r($query, TRUE),
      ]);
    }
    try {
      if (empty($args['keyword'])) {
        $query_hash = md5(implode($query));
        $allow_expired = FALSE;
        foreach (static::AUTO_UPDATED_TAGS_QUERIES as $candidate) {
          if (md5(implode($query)) == $query_hash) {
            $allow_expired = TRUE;
            break;
          }
        }

        if ($cache_item = $this->cache->get(self::CID_TAGS . '_' . $query_hash, $allow_expired)) {
          return $cache_item->data;
        }
        else {
          $result = $this->getAssetBankManager()->getTags($query)->wait();
          $this->cache->set(self::CID_TAGS . '_' . $query_hash, $result, ($this->configFactory->get('bynder.settings')->get('cache_lifetime') + $this->time->getRequestTime()));
          return $result;
        }
      }
      else {
        $result = $this->getAssetBankManager()->getTags($query)->wait();
      }

      if ($bynder_configuration->get('debug')) {
        $this->loggerFactory->get('bynder')->debug('Method: %method returns: @result', [
          '%method' => 'getTags',
          '@result' => print_r($result, TRUE),
        ]);
      }
      return $result;
    } catch (\Exception $e) {
      if ($bynder_configuration->get('debug')) {
        $this->loggerFactory->get('bynder')
          ->error('Method: %method throws error with message: @message', [
            '%method' => 'getTags',
            '@message' => $e->getMessage(),
          ]);
      }
      throw $e;
    }
  }

  public function getIntegrationId() {
    return self::BYNDER_INTEGRATION_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function addAssetUsage($asset_id, $usage_url, $creation_date, $additional_info = NULL) {
    $usage_properties = [
      'integration_id' => self::BYNDER_INTEGRATION_ID,
      'asset_id' => $asset_id,
      'timestamp' => $creation_date,
      'uri' => $usage_url->setAbsolute(TRUE)->toString(),
      'additional' => $additional_info,
    ];

    return $this->getAssetBankManager()->createUsage($usage_properties)->wait();
  }

  /**
   * {@inheritdoc}
   */
  public function removeAssetUsage($asset_id, $usage_url = NULL) {
    $usageProperties = [
      'integration_id' => self::BYNDER_INTEGRATION_ID,
      'asset_id' => $asset_id,
      'uri' => $usage_url,
    ];

    return $this->getAssetBankManager()->deleteUsage($usageProperties)->wait();
  }

  /**
   * {@inheritdoc}
   */
  public function getAssetUsages($asset_id) {
    return $this->getAssetBankManager()
      ->getUsage(['asset_id' => $asset_id])
      ->wait();
  }

  /**
   * {@inheritdoc}
   */
  public function __call($method, $args) {
    $bynder_configuration = $this->configFactory->get('bynder.settings');
    if ($bynder_configuration->get('debug')) {
      $this->loggerFactory->get('bynder')->debug('Method: %method is called with arguments: @args', [
        '%method' => $method,
        '@args' => print_r($args, TRUE),
      ]);
    }
    try {
      // TODO cache getMediaItem()?
      if (empty($args) && in_array($method, array_keys(self::CACHED_CALLS))) {
        if ($cache_item = $this->cache->get(self::CACHED_CALLS[$method], TRUE)) {
          return $cache_item->data;
        }
        else {
          $result = call_user_func_array([$this->getAssetBankManager(), $method], $args)->wait();
          $this->cache->set(self::CACHED_CALLS[$method], $result, ($this->configFactory->get('bynder.settings')->get('cache_lifetime') + $this->time->getRequestTime()));
          return $result;
        }
      }
      else {
        $result = call_user_func_array([$this->getAssetBankManager(), $method], $args)->wait();
      }
      if ($bynder_configuration->get('debug')) {
        $this->loggerFactory->get('bynder')->debug('Method: %method returns: @result', [
          '%method' => $method,
          '@result' => print_r($result, TRUE),
        ]);
      }
      return $result;
    } catch (\Exception $e) {
      if ($bynder_configuration->get('debug')) {
        $this->loggerFactory->get('bynder')
          ->error('Method: %method throws error with message: @message', [
            '%method' => $method,
            '@message' => $e->getMessage(),
          ]);
      }
      throw $e;
    }
  }

}
