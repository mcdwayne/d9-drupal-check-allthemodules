<?php

namespace Drupal\popup_onload\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PopupOnloadController.
 */
class PopupOnloadController extends ControllerBase {

  /**
   * Get popup.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Return response with popup settings.
   */
  public function getPopup() {
    $response = new Response();
    if (popup_onload_check_path()) {
      if ($popup_onload = popup_onload_choose_popup()) {
        if (popup_onload_check_display_conditions($popup_onload)) {
          $popup_settings = popup_onload_prepare_popup($popup_onload);
          $response->setContent(json_encode($popup_settings));
          drupal_static(POPUP_ONLOAD_IS_POPUP_ADDED, TRUE);
          popup_onload_save_time_cookie();
        }
      }
    }
    return $response;
  }

}
