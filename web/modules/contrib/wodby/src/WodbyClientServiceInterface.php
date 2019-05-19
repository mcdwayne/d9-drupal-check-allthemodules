<?php

namespace Drupal\wodby;

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
 * Interface WodblyClientServiceInterface.
 */
interface WodbyClientServiceInterface {

  /**
   * @return \Wodby\Api\Configuration   */
  public function getWodbyConfigFromEnv(): Configuration;

  /**
   * @param string $api_key
   *
   * @return \Wodby\Api\Configuration   */
  public function getWodbyConfig(string $api_key): Configuration;

  /**
   * @param \Wodby\Api\Configuration         $conf
   *
   * @param \GuzzleHttp\ClientInterface $client
   *
   * @return \Wodby\Api\Client\ApplicationApi
   */
  public function getAppApi(Configuration $conf = NULL, ClientInterface $client = NULL): ApplicationApi;

  /**
   * @param \Wodby\Api\Configuration    $conf
   *
   * @param \GuzzleHttp\ClientInterface $client
   * @return \Wodby\Api\Client\BackupApi
   */
  public function getBackupApi(Configuration $conf = NULL, ClientInterface $client = NULL): BackupApi;

  /**
   * @param \Wodby\Api\Configuration    $conf
   *
   * @param \GuzzleHttp\ClientInterface $client
   * @return \Wodby\Api\Client\DomainApi
   */
  public function getDomainApi(Configuration $conf = NULL, ClientInterface $client = NULL): DomainApi;

  /**
   * @param \Wodby\Api\Configuration    $conf
   *
   * @param \GuzzleHttp\ClientInterface $client
   * @return \Wodby\Api\Client\GitRepositoryApi
   */
  public function getGitRepoApi(Configuration $conf = NULL, ClientInterface $client = NULL): GitRepositoryApi;

  /**
   * @param \Wodby\Api\Configuration    $conf
   *
   * @param \GuzzleHttp\ClientInterface $client
   * @return \Wodby\Api\Client\InstanceApi
   */
  public function getInstanceApi(Configuration $conf = NULL, ClientInterface $client = NULL): InstanceApi;

  /**
   * @param \Wodby\Api\Configuration    $conf
   *
   * @param \GuzzleHttp\ClientInterface $client
   * @return \Wodby\Api\Client\OrganizationApi
   */
  public function getOrgApi(Configuration $conf = NULL, ClientInterface $client = NULL): OrganizationApi;

  /**
   * @param \Wodby\Api\Configuration    $conf
   *
   * @param \GuzzleHttp\ClientInterface $client
   * @return \Wodby\Api\Client\ServerApi
   */
  public function getServerApi(Configuration $conf = NULL, ClientInterface $client = NULL): ServerApi;

  /**
   * @param \Wodby\Api\Configuration    $conf
   *
   * @param \GuzzleHttp\ClientInterface $client
   * @return \Wodby\Api\Client\StackApi
   */
  public function getStackApi(Configuration $conf = NULL, ClientInterface $client = NULL): StackApi;

  /**
   * @param \Wodby\Api\Configuration    $conf
   *
   * @param \GuzzleHttp\ClientInterface $client
   * @return \Wodby\Api\Client\TaskApi
   */
  public function getTaskApi(Configuration $conf = NULL, ClientInterface $client = NULL): TaskApi;

  /**
   * @param \Wodby\Api\Configuration    $conf
   *
   * @param \GuzzleHttp\ClientInterface $client
   * @return \Wodby\Api\Client\UserApi
   */
  public function getUserApi(Configuration $conf = NULL, ClientInterface $client = NULL): UserApi;

  /**
   * @return null|string
   */
  public function getSiteAppUuid(): ?string;

  /**
   * Stores in the config the App UUID.
   *
   * @param string $uuid
   */
  public function setSiteAppUuid(string $uuid);

  /**
   * Gets a list of Instance UUIDs to use for quick deploy.
   *
   * @return string[]
   */
  public function getQuickDeployList(): array;
}
