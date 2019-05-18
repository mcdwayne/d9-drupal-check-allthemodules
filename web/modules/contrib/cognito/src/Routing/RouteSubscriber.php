<?php

namespace Drupal\cognito\Routing;

use Drupal\cognito\CognitoFlowManager;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Cognito route subscriber.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The cognito flow plugin manager.
   *
   * @var \Drupal\cognito\CognitoFlowManager
   */
  protected $cognitoFlowManager;

  /**
   * RouteSubscriber constructor.
   *
   * @param \Drupal\cognito\CognitoFlowManager $cognitoFlowManager
   *   The cognito flow plugin manager.
   */
  public function __construct(CognitoFlowManager $cognitoFlowManager) {
    $this->cognitoFlowManager = $cognitoFlowManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $flow = $this->cognitoFlowManager->getSelectedFlow();

    $route = $collection->get('user.login');
    $route->setDefault('_form', $flow->getFormClass('login'));

    $route = $collection->get('user.pass');
    $route->setDefault('_form', $flow->getFormClass('password_reset'));

    $route = $collection->get('user.admin_create');
    $route->setDefault('_entity_form', 'user.admin_register');
  }

}
