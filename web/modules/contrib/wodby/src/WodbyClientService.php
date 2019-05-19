<?php

namespace Drupal\wodby;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use Wodby\Api\Client\ApplicationApi;
use Wodby\Api\Client\BackupApi;
use Wodby\Api\Client\DomainApi;
use Wodby\Api\Client\GitRepositoryApi;
use Wodby\Api\Client\InstanceApi;
use Wodby\Api\Client\OrganizationApi;
use Wodby\Api\Client\ServerApi;
use Wodby\Api\Client\StackApi;
use Wodby\Api\Client\TaskApi;
use Wodby\Api\Client\UserApi;
use Wodby\Api\Configuration;

/**
 * Class WodblyClientService.
 */
class WodbyClientService implements WodbyClientServiceInterface {

  const API_KEY_TYPE = 'X-API-KEY';

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new WodblyClientService object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * @inheritDoc
   */
  public function getWodbyConfigFromEnv(): Configuration {
    $is_debug = boolval(getenv('DEBUG'));
    $api_key = getenv('WODBY_API_KEY');

    if (empty($api_key)) {
      throw new \RuntimeException("Missing WODBY_API_KEY in environment.");
    }

    $c = new Configuration();
    return $c->setApiKey(self::API_KEY_TYPE, $api_key)
      ->setDebug($is_debug);
  }

  /**
   * @inheritDoc
   */
  public function getWodbyConfig(string $api_key, bool $debug = FALSE): Configuration {
    $c = new Configuration();
    return $c->setApiKey(self::API_KEY_TYPE, $api_key)
      ->setDebug($debug);
  }

  /**
   * @inheritDoc
   */
  public function getAppApi(Configuration $conf = NULL, ClientInterface $client = NULL): ApplicationApi {
    $api_conf = $conf ?? $this->getWodbyConfigFromEnv();
    $http_client = $client ?? \Drupal::httpClient();

    return new ApplicationApi($http_client, $api_conf);
  }

  /**
   * @inheritDoc
   */
  public function getBackupApi(Configuration $conf = NULL, ClientInterface $client = NULL): BackupApi {
    $api_conf = $conf ?? $this->getWodbyConfigFromEnv();
    $http_client = $client ?? \Drupal::httpClient();

    return new BackupApi($http_client, $api_conf);
  }

  /**
   * @inheritDoc
   */
  public function getDomainApi(Configuration $conf = NULL, ClientInterface $client = NULL): DomainApi {
    $api_conf = $conf ?? $this->getWodbyConfigFromEnv();
    $http_client = $client ?? \Drupal::httpClient();

    return new DomainApi($http_client, $api_conf);
  }

  /**
   * @inheritDoc
   */
  public function getGitRepoApi(Configuration $conf = NULL, ClientInterface $client = NULL): GitRepositoryApi {
    $api_conf = $conf ?? $this->getWodbyConfigFromEnv();
    $http_client = $client ?? \Drupal::httpClient();

    return new GitRepositoryApi($http_client, $api_conf);
  }

  /**
   * @inheritDoc
   */
  public function getInstanceApi(Configuration $conf = NULL, ClientInterface $client = NULL): InstanceApi {
    $api_conf = $conf ?? $this->getWodbyConfigFromEnv();
    $http_client = $client ?? \Drupal::httpClient();

    return new InstanceApi($http_client, $api_conf);
  }

  /**
   * @inheritDoc
   */
  public function getOrgApi(Configuration $conf = NULL, ClientInterface $client = NULL): OrganizationApi {
    $api_conf = $conf ?? $this->getWodbyConfigFromEnv();
    $http_client = $client ?? \Drupal::httpClient();

    return new OrganizationApi($http_client, $api_conf);
  }

  /**
   * @inheritDoc
   */
  public function getServerApi(Configuration $conf = NULL, ClientInterface $client = NULL): ServerApi {
    $api_conf = $conf ?? $this->getWodbyConfigFromEnv();
    $http_client = $client ?? \Drupal::httpClient();

    return new ServerApi($http_client, $api_conf);
  }

  /**
   * @inheritDoc
   */
  public function getStackApi(Configuration $conf = NULL, ClientInterface $client = NULL): StackApi {
    $api_conf = $conf ?? $this->getWodbyConfigFromEnv();
    $http_client = $client ?? \Drupal::httpClient();

    return new StackApi($http_client, $api_conf);
  }

  /**
   * @inheritDoc
   */
  public function getTaskApi(Configuration $conf = NULL, ClientInterface $client = NULL): TaskApi {
    $api_conf = $conf ?? $this->getWodbyConfigFromEnv();
    $http_client = $client ?? \Drupal::httpClient();

    return new TaskApi($http_client, $api_conf);
  }

  /**
   * @inheritDoc
   */
  public function getUserApi(Configuration $conf = NULL, ClientInterface $client = NULL): UserApi {
    $api_conf = $conf ?? $this->getWodbyConfigFromEnv();
    $http_client = $client ?? \Drupal::httpClient();

    return new UserApi($http_client, $api_conf);
  }

  /**
   * @inheritDoc
   */
  public function getSiteAppUuid(): ?string {
    return $this->settings()->get('app_uuid');
  }

  /**
   * @inheritDoc
   */
  public function setSiteAppUuid(string $uuid) {
    $this->settings(TRUE)
      ->set('app_uuid', $uuid)
      ->save();
  }

  /**
   * @inheritDoc
   */
  public function getQuickDeployList(): array {
    $list = $this->settings()->get('instances.quick_deploy');
    return is_array($list) ? $list : [];
  }


  /**
   * Get the module's configuration.
   *
   * @param bool $editable
   *
   * @return \Drupal\Core\Config\Config
   */
  protected function settings($editable = FALSE): Config {
    if ($editable) {
      return $this->configFactory->getEditable('wodby.settings');
    }

    return $this->configFactory->get('wodby.settings');
  }
}
