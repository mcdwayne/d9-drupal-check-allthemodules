<?php

namespace Drupal\popup_after_login\Controller;

use Drupal\Core\Controller\ControllerBase;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller routines for popup_after_login routes.
 */
class PopupAfterLoginVerification extends ControllerBase {

  /**
   * Callback for `popup_after_login_get_results.json` API method.
   */
  public function popupAfterLoginResponse(Request $request) {

    $config = $this->config('popup_after_login_config.settings');
    $selected_roles = $config->get('popup_after_login_choose_role');
    $current_user = \Drupal::currentUser();
    $current_user_roles = $current_user->getRoles();
    $has_valid_user = array_intersect($current_user_roles, $selected_roles);
    $username = $current_user->getAccountName();
    if ($has_valid_user) {
      if (isset($_SESSION['first_' . $username]) && $config->get('popup_after_login_first_title')) {
        $response['title'] = $config->get('popup_after_login_first_title');
        $response['message'] = $config->get('popup_after_login_first_message');
        unset($_SESSION['first_' . $username]);
        return new JsonResponse($response);
      }
      elseif (isset($_SESSION['always_' . $username]) && $config->get('popup_after_login_first_title_always')) {
        $response['title'] = $config->get('popup_after_login_first_title_always');
        $response['message'] = $config->get('popup_after_login_first_message_always');
        unset($_SESSION['always_' . $username]);
        return new JsonResponse($response);
      }
      else {
        $response['stop'] = 'stop';
        return new JsonResponse($response);
      }
    }
    else {
      $response['stop'] = $has_valid_user;
      return new JsonResponse($response);
    }
  }

}
