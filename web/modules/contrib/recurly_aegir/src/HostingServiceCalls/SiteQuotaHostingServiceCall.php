<?php

namespace Drupal\recurly_aegir\HostingServiceCalls;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\node\NodeInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

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
  protected $quota;

  /**
   * The limit of the quota to set.
   *
   * @var int
   */
  protected $limit;

  /**
   * {@inheritdoc}
   *
   * @param string $quota
   *   The ID of the quota to set.
   * @param string $limit
   *   The limit of the quota to set.
   */
  public static function create(ContainerInterface $container, NodeInterface $site, $quota, $limit) {
    return new static(
      $container->get('logger.factory')->get('recurly_aegir'),
      $container->get('http_client'),
      $container->get('config.factory')->get('recurly_aegir.settings'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('module_handler'),
      $site,
      $quota,
      $limit
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param string $quota
   *   The ID of the quota to set.
   * @param string $limit
   *   The limit of the quota to set.
   */
  public function __construct(
    LoggerInterface $logger,
    Client $http_client,
    ImmutableConfig $recurly_config,
    Request $current_request,
    ModuleHandlerInterface $module_handler,
    NodeInterface $site,
    $quota,
    $limit
  ) {
    parent::__construct($logger, $http_client, $recurly_config, $current_request, $module_handler, $site);

    if (!is_array($quota)) {
      throw new \Exception('Quota hosting service calls must be provided with a quota ID on construction.');
    }
    if (!is_array($limit)) {
      throw new \Exception('Quota hosting service calls must be provided with a quota limit on construction.');
    }

    $this->quota = $quota;
    $this->limit = $limit;
  }

  /**
   * {@inheritdoc}
   *
   * Set a site's quota.
   */
  protected function execute() {
    $this->sendRequestAndReceiveResponse('variables', [
      'site' => $this->getSiteName(),
      'name' => $this->quota,
      'value' => $this->limit,
    ]);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function recordSuccessLogMessage() {
    $this->logger
      ->info('Remote site %sitename: The limit for quota %quota was set to %limit via %class.', [
        '%sitename' => $this->getSiteName(),
        '%quota' => $this->quota,
        '%limit' => $this->limit,
        '%class' => $this->getClassName(),
      ]);
    return $this;
  }

}
