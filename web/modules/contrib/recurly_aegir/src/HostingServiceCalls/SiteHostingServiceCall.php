<?php

namespace Drupal\recurly_aegir\HostingServiceCalls;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\node\NodeInterface;
use Drupal\recurly_aegir\Exceptions\TaskCreationFailedException;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for sending site requests via the hosting Web service.
 */
abstract class SiteHostingServiceCall extends HostingServiceCall {

  /**
   * The site to act upon.
   *
   * @var Drupal\node\NodeInterface
   */
  protected $site;

  /**
   * {@inheritdoc}
   *
   * @param Drupal\node\NodeInterface $site
   *   The site to act upon.
   */
  public function __construct(
    LoggerInterface $logger,
    Client $http_client,
    ImmutableConfig $recurly_config,
    Request $current_request,
    ModuleHandlerInterface $module_handler,
    NodeInterface $site
  ) {
    parent::__construct($logger, $http_client, $recurly_config, $current_request, $module_handler);
    if (empty($site)) {
      throw new \Exception('Site Web service calls must be provided with a site node on construction.');
    }
    $this->site = $site;
  }

  /**
   * Returns the name of the site.
   */
  public function getSiteName() {
    return $this->site->getTitle() . '.' . $this->currentRequest->getHost();
  }

  /**
   * {@inheritdoc}
   *
   * Log the action to the site node as well as the general system log.
   */
  public function performActionAndLogResults() {
    try {
      parent::performActionAndLogResults()->logActionToSite();
    }
    catch (TaskCreationFailedException $e) {
      watchdog_exception('recurly_aegir', $e);
    }
    return $this;
  }

  /**
   * Log the action and associated details to the site node.
   */
  protected function logActionToSite() {
    $aegir_url = Request::create($this->getAegirServiceEndpoint())->getSchemeAndHttpHost();

    $this->site->field_site_tasks->appendItem([
      'uri' => $aegir_url . $this->getRemoteTargetPath(),
      'title' => date("Y-m-d \@ H:i:s") . ' » ' . $this->getActionPerformed(),
    ]);

    return $this;
  }

  /**
   * Returns the path of the remote object being acted upon.
   */
  protected function getRemoteTargetPath() {
    return '/node/' . $this->getRemoteTargetId();
  }

  /**
   * Returns the ID of the remote object being acted upon.
   */
  protected function getRemoteTargetId() {
    return $this->getSiteName();
  }

}
