<?php

namespace Drupal\iots\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

/**
 * Class DefaultController.
 */
class DefaultController extends ControllerBase {

  /**
   * Home.
   */
  public function home() {
    return [
      '#markup' => 'IOTs',
    ];
  }

  /**
   * Hello.
   *
   * @return string
   *   Return Hello string.
   */
  public function hello($name) {
    if ($name == 'meteo') {
      $entity_type = 'iots_measure';
      $storage = \Drupal::entityManager()->getStorage($entity_type);
      $date = format_date(REQUEST_TIME, 'custom', 'd.m');

      $query = \Drupal::request()->query;
      $t = $query->get('temp');
      $rh = $query->get('rh');
      $c = $query->get('C');
      $mm = $query->get('mm');
      $tout = $query->get('tout');

      $temp = [
        'channel' => 1,
        'measure' => (float) $t,
        'status' => ($t > 0) ? 1 : 0,
      ];
      $humidity = [
        'channel' => 2,
        'measure' => (float) $rh,
        'status' => ($rh > 0) ? 1 : 0,
      ];
      $pressure = [
        'channel' => 3,
        'measure' => round((float) $mm, 6),
        'status' => ($t > 0) ? 1 : 0,
      ];
      $temperature = [
        'channel' => 4,
        'measure' => round((float) $c - 1.65, 6),
        'status' => ($c > 0) ? 1 : 0,
      ];
      $tempout = [
        'channel' => 5,
        'measure' => round((float) $tout, 6),
        'status' => ($tout > 0) ? 1 : 0,
      ];
      $storage->create($temp)->save(TRUE);
      $storage->create($humidity)->save(TRUE);
      $storage->create($pressure)->save(TRUE);
      $storage->create($temperature)->save(TRUE);
      $storage->create($tempout)->save(TRUE);
      \Drupal::logger('iot')->notice("{$date}: $t/$rh $c/$mm $tout");
      $msg = [
        'status' => 'ok',
        'time' => REQUEST_TIME,
      ];
      $json = json_encode($msg, JSON_UNESCAPED_UNICODE);
    }
    else {
      $json = json_encode(['time' => REQUEST_TIME], JSON_UNESCAPED_UNICODE);
    }
    $response = new Response($json);
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

}
