<?php

namespace Drupal\simple_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\simple_analytics\SimpleAnalyticsService;
use Drupal\simple_analytics\SimpleAnalyticsActions;

/**
 * Simple Analytics Functions.
 */
class SimpleAnalyticsTracker extends ControllerBase {

  /**
   * Input / Process a log.
   */
  public function process() {

    $output = [];
    $output[] = ["rasult" => "OK"];

    // Clean REQUEST.
    foreach ($_POST as $key => $value) {
      if (strstr($key, 'amp;')) {

        $_POST[str_replace('amp;', '', $key)] = $value;
      }
    }
    $data = [];
    $data['REQUEST_URI'] = (!empty($_POST['URL'])) ? $_POST['URL'] : "";
    if (empty($data['REQUEST_URI'])) {
      $data['REQUEST_URI'] = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";
    }
    $data['TITLE'] = (!empty($_POST['TITLE'])) ? $_POST['TITLE'] : "";
    $data['CAMP'] = (!empty($_POST['CAMP'])) ? $_POST['CAMP'] : "";
    $data['REFERER'] = (!empty($_POST['REFERER'])) ? $_POST['REFERER'] : "";
    $data['MOBILE'] = (!empty($_POST['MOBILE'])) ? $_POST['MOBILE'] : "";
    $data['SCREEN'] = (!empty($_POST['SCREEN'])) ? $_POST['SCREEN'] : "";
    $data['LANGUAGE'] = (!empty($_POST['LANGUAGE'])) ? $_POST['LANGUAGE'] : "";
    $data['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : "";
    $data['REMOTE_ADDR'] = simple_analytics_get_client_ip();
    $data['BOT'] = SimpleAnalyticsService::botDetecte();
    $data['HTTP_HOST'] = $_SERVER['HTTP_HOST'];
    $data['CLOSE'] = (empty($_POST['CLOSE'])) ? FALSE : TRUE;
    $data['SERVEUR'] = json_encode($_SERVER);

    // Add to stat.
    SimpleAnalyticsActions::setStat($data);

    return new JsonResponse($output);
  }

}
