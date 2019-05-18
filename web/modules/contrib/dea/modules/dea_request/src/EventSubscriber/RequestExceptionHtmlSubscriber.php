<?php

namespace Drupal\dea_request\EventSubscriber;

use Drupal\Core\EventSubscriber\DefaultExceptionHtmlSubscriber;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\dea\RequirementDiscovery;
use Drupal\dea_request\Routing\RequestableRouteEnhancer;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

class RequestExceptionHtmlSubscriber extends DefaultExceptionHtmlSubscriber {

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var \Drupal\dea\RequirementDiscoveryInterface
   */
  protected $requirementDiscovery;

  /**
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * {@inheritdoc}
   */
  protected static function getPriority() {
    return 10;
  }
  
  public function __construct(
    HttpKernelInterface $http_kernel,
    LoggerInterface $logger,
    RedirectDestinationInterface $redirect_destination,
    UrlMatcherInterface $access_unaware_router,
    AccountInterface $account,
    RouteMatchInterface $route_match,
    RequirementDiscovery $requirement_discovery
  ) {
    parent::__construct($http_kernel, $logger, $redirect_destination, $access_unaware_router);
    $this->account = $account;
    $this->routeMatch = $route_match;
    $this->requirementDiscovery = $requirement_discovery;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public function on403(GetResponseForExceptionEvent $event) {
    if ($this->account->hasPermission('request dynamic entity access')) {
      if ($entity_operation = $event->getRequest()->get(RequestableRouteEnhancer::ENTITY_OPERATION)) {
        list($entity, $operation) = array_values($entity_operation);
        if (count($this->requirementDiscovery->requirements($entity, $entity, $operation)) > 0) {
          $this->makeSubrequest($event, Url::fromRoute('dea_request.request', [
            'entity_type' => $entity->getEntityTypeId(),
            'entity_id' => $entity->id(),
            'operation' => $operation,
          ], [
            'query' => $this->redirectDestination->getAsArray(),
          ])->toString(), Response::HTTP_FORBIDDEN);
          return;
        }
      }
    }
    parent::on403($event);
  }

}