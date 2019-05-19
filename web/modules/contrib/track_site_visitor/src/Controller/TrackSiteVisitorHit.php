<?php

/**
 * @file
 * Contains \Drupal\track_site_visitor\Controller\TrackSiteVisitorHit.
 */

namespace Drupal\track_site_visitor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller routines for help routes.
 */
class TrackSiteVisitorHit extends ControllerBase {

  /**
   * Save the
   *
   * @return string
   *   An HTML string representing the contents of help page.
   */
  public function saveHit($lat, $lon) {
    $request_parameter = \Drupal::request()->request;
    $tracked_detail = [
      'street_number' => $request_parameter->get('street_number'),
      'route' => $request_parameter->get('route'),
      'sublocality_level_2' => $request_parameter->get('sublocality_level_2,sublocality,political'),
      'sublocality_level_1' => $request_parameter->get('sublocality_level_1,sublocality,political'),
      'locality' => $request_parameter->get('locality,political'),
      'administrative_area_level_2' => $request_parameter->get('administrative_area_level_2,political'),
      'administrative_area_level_1' => $request_parameter->get('administrative_area_level_1,political'),
      'country' => $request_parameter->get('country,political'),
      'postal_code' => $request_parameter->get('postal_code'),
    ];
    $tracked_detail = json_encode($tracked_detail);
    \Drupal::database()->insert('track_site_visitor')
      ->fields(array(
        'uid' => \Drupal::currentUser()->id(),
        'url' => \Drupal::request()->server->get('HTTP_REFERER'),
        'timestamp' => time(),
        'tracked_detail' => $tracked_detail,
      ))
      ->execute();
    $output = array(
      '#markup' => 'Saved the tracked detail..!',
    );
    return $output;
  }

}
