<?php

namespace Drupal\aegir_site_subscriptions\HostingServiceCalls;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Sets a quota for a site.
 */
class SiteQuotaHostingServiceCall extends SiteHostingServiceCall {

  /**
   * The activity that was performed by this hosting service call's execution.
   */
  const ACTION_PERFORMED = 'Site quota set';

  /**
   * The ID of the quota to set.
   *
   * @var string
   */
  protected $quotaId;

  /**
   * The limit of the quota to set.
   *
   * @var int
   */
  protected $quotaLimit;

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function __construct(
    LoggerChannelFactory $logger_factory,
    Client $http_client,
    ConfigFactory $config_factory,
    RequestStack $requestStack,
    MessengerInterface $user_messenger
  ) {
    parent::__construct($logger_factory, $http_client, $config_factory, $requestStack, $user_messenger);
    $this->quotaId = NULL;
    $this->quotaLimit = NULL;
  }

  /**
   * Sets the quota.
   *
   * @param $quota_id
   *   The ID of the quota to set.
   * @param $quota_limit
   *   The limit to set.

   * @return $this
   *   The object itself for method chaining.
   *
   * @throws \Exception
   */
  public function setQuota($quota_id, $quota_limit) {
    if (empty($quota_id)) {
      throw new \Exception('Quota hosting service calls must be provided with a valid quota ID.');
    }
    $this->quotaId = $quota_id;
    $this->quotaLimit = $quota_limit;
    return $this;
  }

  /**
   * Fetches the quota ID.
   *
   * @return string
   * @throws \Exception
   */
  public function getQuotaId() {
    if (is_null($this->quotaId)) {
      throw new \Exception('Quota hosting service calls must be provided with a valid quota ID.');
    }
    return $this->quotaId;
  }

  /**
   * Fetches the quota limit.
   *
   * @return int
   * @throws \Exception
   */
  public function getQuotaLimit() {
    if (is_null($this->quotaLimit)) {
      throw new \Exception('Quota hosting service calls must be provided with a valid quota ID.');
    }
    return $this->quotaLimit;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function execute() {
    $this->sendRequestAndReceiveResponse('variables', [
      'site' => $this->getSiteName(),
      'name' => $this->getQuotaId(),
      'value' => $this->getQuotaLimit(),
    ]);

    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \ReflectionException
   * @throws \Exception
   */
  protected function recordSuccessLogMessage() {
    $this->logger
      ->info('Remote site %sitename: The limit for quota %quota was set to %limit via %class.', [
        '%sitename' => $this->getSiteName(),
        '%quota' => $this->getQuotaId(),
        '%limit' => $this->getQuotaLimit(),
        '%class' => $this->getClassName(),
      ]);
    return $this;
  }

}
