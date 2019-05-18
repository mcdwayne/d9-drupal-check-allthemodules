<?php

namespace Drupal\aegir_site_subscriptions\HostingServiceCalls;

use Drupal\aegir_site_subscriptions\Exceptions\SiteServiceMissingSiteException;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\node\NodeInterface;
use Drupal\aegir_site_subscriptions\Exceptions\TaskCreationFailedException;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class for sending site requests via the hosting Web service.
 */
abstract class SiteHostingServiceCall extends HostingServiceCall {

  /**
   * The current HTTP/S request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The user messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $userMessenger;

  /**
   * The site to act upon.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $site;

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current HTTP/S request.
   * @param \Drupal\Core\Messenger\MessengerInterface $user_messenger
   *   The user messenger.
   *
   * @throws \Exception
   */
  public function __construct(
    LoggerChannelFactory $logger_factory,
    Client $http_client,
    ConfigFactory $config_factory,
    RequestStack $request_stack,
    MessengerInterface $user_messenger
  ) {
    parent::__construct($logger_factory, $http_client, $config_factory);
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->userMessenger = $user_messenger;
    $this->site = NULL;
  }

  /**
   * Sets the site to act upon.
   *
   * It's necessary to call this method before any other non-static methods.
   *
   * @param \Drupal\node\NodeInterface $site
   *   The site to act upon.
   *
   * @return $this
   *   The object itself, for method chaining.
   *
   * @throws \Exception
   */
  public function setSite(NodeInterface $site) {
    if ($site->getType() != 'aegir_site') {
      throw new SiteServiceMissingSiteException('Only site nodes can be provided to this setter.');
    }
    $this->site = $site;
    return $this;
  }

  /**
   * Fetches the site currently set with the service.
   *
   * @return \Drupal\node\NodeInterface
   *   The site node.
   */
  protected function getSite() {
    if (is_null($this->site)) {
      throw new SiteServiceMissingSiteException('This operation requires that the site service be set with a site.');
    }
    return $this->site;
  }

  /**
   * Returns the name of the site.
   */
  public function getSiteName() {
    return $this->getSite()->getTitle() . '.' . $this->currentRequest->getHost();
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
      watchdog_exception('aegir_site_subscriptions', $e);
    }
    return $this;
  }

  /**
   * Log the action and associated details to the site node.
   */
  protected function logActionToSite() {
    $aegir_url = Request::create($this->getAegirServiceEndpoint())->getSchemeAndHttpHost();

    $this->getSite()->field_site_tasks->appendItem([
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
