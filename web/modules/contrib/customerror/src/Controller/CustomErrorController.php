<?php

namespace Drupal\customerror\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * Controller for errors pages.
 */
class CustomErrorController extends ControllerBase {


  /**
   * This is the method that will get called, with the services above already available.
   *
   * @param int $code
   *   The code of error.
   */
  public function index($code) {
    if (!is_numeric($code)) {
      throw new AccessDeniedHttpException();
    }

    $theme = customerror_get_theme($code);

    if (!empty($theme)) {
      global $custom_theme;
      $custom_theme = $theme;
    }

    switch ($code) {
      case 403:
        $internal_path = substr(\Drupal::request()->getRequestUri(), strlen(base_path()));
        if ($internal_path) {
          $dest = parse_url($internal_path);
          if (isset($dest['query']['destination'])) {
            $_GET['destination'] = ($dest['query']['destination']);
          }
          else {
            $_GET['destination'] = $internal_path;
          }
        }
        else {
          $_GET['destination'] = \Drupal::config('system.site')->get('page.front');
        }
        $_SESSION['destination'] = $_GET['destination'];

      case 404:
      default:

        // Check if we should redirect.
        $destination   = \Drupal::request()->getRequestUri();
        $redirect_list = \Drupal::config('customerror.settings')->get('redirect');
        $redirect_list = !empty($redirect_list) ? explode("\n", $redirect_list) : [];
        foreach ($redirect_list as $item) {
          list($src, $dst) = explode(' ', $item);

          if (isset($src) && isset($dst)) {
            $src = str_replace("/", "\\/", $src);
            $dst = str_replace("\r", "", $dst);

            // In case there are spaces in the URL, we escape them.
            $orig_dst = str_replace(" ", "%20", $destination);
            if (preg_match("/$src/", $orig_dst)) {
              // drupal_goto($dst);
              // return new RedirectResponse(url($dst, array('absolute' => TRUE)));
              $dst = ($dst == '<front>' ? Url::fromRoute($dst)->toString() : $dst);
              header('Location: ' . $dst, TRUE, 302);
              exit();
            }
          }
        }

        // Make sure that we sent an appropriate header.
        customerror_header($code);

        $content = \Drupal::config('customerror.settings')->get("{$code}.body");
        break;
    }

    return [
      '#markup' => $content,
    ];
  }


  /**
   * Title callback.
   *
   * @param int $code
   *   The code of error.
   *
   * @return string
   *   The title to page.
   */
  public function titleCallback($code) {
    return \Drupal::config('customerror.settings')->get("{$code}.title");
  }

}
