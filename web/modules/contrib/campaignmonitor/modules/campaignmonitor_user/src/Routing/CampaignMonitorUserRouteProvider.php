<?php

namespace Drupal\campaignmonitor_user\Routing;

use Symfony\Component\Routing\Route;

/**
 * Provides routes for the user entity.
 */
class CampaignMonitorUserRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $config = \Drupal::config('campaignmonitor_user.settings');
    $routes = [];
    // Returns an array of Route objects.
    $routes['campaignmonitor.user.subscriptions'] = new Route(
    // Path to attach this route to:
      '/user/campaignmonitor',
      // Route defaults:
      [
        '_controller' => '\Drupal\campaignmonitor_user\Controller\CampaignMonitorUserController::subscriptionPage',
        '_title' => $config->get('subscription_heading'),
      ],
      // Route requirements:
      [
        'user'  => '\d+',
        '_user_is_logged_in' => 'TRUE',
      ]
    );
    $routes['campaignmonitor.user.subscriptions_edit'] = new Route(
      // Path to attach this route to:
      '/user/campaignmonitor/{user}/edit',
      // Route defaults:
      [
        '_form' => '\Drupal\campaignmonitor_user\Form\CampaignMonitorUserSubscriptionForm',
        '_title' => $config->get('subscription_heading'),
      ],
      // Route requirements:
      [
        'user'  => '\d+',
        '_user_is_logged_in' => 'TRUE',
      ]
    );
    return $routes;
  }

}
