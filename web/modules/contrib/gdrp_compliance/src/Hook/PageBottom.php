<?php

namespace Drupal\gdrp_compliance\Hook;

use Drupal\Core\Controller\ControllerBase;

/**
 * PreprocessHtml.
 */
class PageBottom extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(array &$page_bottom) {
    $path = \Drupal::service('path.current')->getPath();
    $config = \Drupal::config('gdrp_compliance.settings');
    // Don't display on admin pages.
    list($r, $g, $b) = sscanf($config->get('popup-color'), "#%02x%02x%02x");
    if (substr($path, 0, 7) != '/admin/' && self::display($config)) {
      $page_bottom['gdrp-popup'] = [
        '#theme' => 'gdrp-popup',
        '#data' => [
          'popup_morelink' => $config->get('popup-morelink'),
          'popup_position' => $config->get('popup-position'),
          'popup_color' => "rgba($r, $g, $b, 0.9)",
          'popup_text' => $config->get('popup-text'),
          'button_color' => $config->get('button-color'),
          'button_text' => $config->get('button-text'),
        ],
        '#attached' => [
          'library' => ['gdrp_compliance/popup'],
        ],
      ];
    }
  }

  /**
   * Display Rules.
   */
  public static function display($config) {
    $display = FALSE;
    if (\Drupal::currentUser()->id()) {
      if ($config->get('popup-users')) {
        $display = TRUE;
      }
    }
    elseif ($config->get('popup-guests')) {
      $display = TRUE;
    }
    return $display;
  }

}
