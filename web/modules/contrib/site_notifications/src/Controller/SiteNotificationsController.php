<?php

namespace Drupal\site_notifications\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\site_notifications\SiteNotificationsHelper;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default controller for the site_notifications module.
 */
class SiteNotificationsController extends ControllerBase {

  /**
   * Implements: getUpdatedNotifications().
   *
   * Call to this finction is made from js to asynchronousely update content.
   */
  public function getUpdatedNotifications() {
    $block_content = SiteNotificationsHelper::getNotificationsData();

    $response = new Response();
    $response->setContent(json_encode($block_content));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

  /**
   * Returns a simple page with listing of all notifications.
   *
   * @return array
   *   A simple renderable array.
   */
  public function notificationListing() {
    // We pass parameter 1 to tell function that we want listing page data.
    $block_content = SiteNotificationsHelper::getNotificationsData(1);

    $element = [
      '#title'              => 'All Notifications',
      '#theme'              => 'listing',
      '#all_notifications'  => $block_content['output'],
      '#notification_count' => $block_content['count'],
    ];
    return $element;
  }

}
