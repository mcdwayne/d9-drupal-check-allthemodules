<?php

namespace Drupal\popup_message\Helper;

use Drupal\Component\Utility\Unicode;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PopupMessageStatus.
 *
 * @package Drupal\popup_message\Helper
 */
class PopupMessageStatus {

  /**
   * Check and send popup status to js.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Popup status in json.
   */
  public function check() {

    $config = \Drupal::configFactory()->get('popup_message.settings');
    // Get popup message visibility settings.
    $visibility = $config->get('visibility') ? $config->get('visibility') : 0;

    // Get popup message visibility pages settings.
    $visibility_pages = $config->get('visibility_pages') ? $config->get('visibility_pages') : '';

    // Predefine value.
    $page_match = TRUE;

    // Limited visibility popup message must list at least one page.
    $status = TRUE;
    if ($visibility == 1 && empty($visibility_pages)) {
      $status = FALSE;
    }

    // Match path if necessary.
    if ($visibility_pages && $status) {
      // Convert path to lowercase. This allows comparison of the same path
      // with different case. Ex: /Page, /page, /PAGE.
      $real_path = $_GET['popup_path'];
      if ($real_path == '/') {
        $real_path = \Drupal::configFactory()
          ->get('system.site')
          ->get('page.front');
      }
      else {
        $real_path = substr($real_path, 1);
      }
      $pages = Unicode::strtolower($visibility_pages);

      if ($visibility < 2) {
        // Convert the Drupal path to lowercase.
        $path = Unicode::strtolower($real_path);
        // Compare the lowercase internal and lowercase path alias (if any).
        $page_match = \Drupal::service('path.matcher')
          ->matchPath($path, $pages);

        $page_match = !($visibility xor $page_match);
      }
      else {
        $page_match = FALSE;
      }
    }

    if ($page_match) {
      $show_popup = 1;
    }
    else {
      $show_popup = 0;
    }

    $response = new Response();
    $response->setContent(json_encode(array('status' => $show_popup)));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

}
