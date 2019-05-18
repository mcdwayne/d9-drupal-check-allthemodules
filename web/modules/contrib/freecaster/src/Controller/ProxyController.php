<?php

namespace Drupal\freecaster\Controller;

use Drupal\Component\Plugin\Exception\ExceptionInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\freecaster\FcapiUtils;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class FCException.
 *
 * @package Drupal\freecaster\Controller
 */
class FCException implements ExceptionInterface {}

/**
 * Class ProxyController.
 *
 * @package Drupal\freecaster\Controller
 */
class ProxyController extends ControllerBase {

  /**
   *
   */
  public function proxy() {

    if (!\Drupal::currentUser()->hasPermission('use freecaster')) {
      echo freecaster_json_error($this->t('Access denied'));
      return;
    };

    $params = array();
    foreach ($_GET as $name => $value) {
      if ($name == 'method' || $name == 'q') {
        continue;
      }
      $params[$name] = $value;
    }
    $api_response = array();
    switch ($_GET['method']) {
      case 'upload_video':
        try {
          $video_id = $params['video_id'];
          unset($params['video_id']);
          $api_response = FcapiUtils::fcApiCall('upload_video/' . $video_id, $params);
        }
        catch (FCException $e) {
          echo freecaster_json_error($e->getMessage());
        }
        break;

      default:
        echo freecaster_json_error($this->t('Invalid method'));
        break;
    }
    return new JsonResponse($api_response);
  }

}
