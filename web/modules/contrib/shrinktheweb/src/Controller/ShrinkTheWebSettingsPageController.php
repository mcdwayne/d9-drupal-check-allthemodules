<?php

namespace Drupal\shrinktheweb\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class ShrinkTheWebSettingsPageController.
 *
 * @package Drupal\shrinktheweb\Controller
 */
class ShrinkTheWebSettingsPageController extends ControllerBase {

  public function renderSettingsPage() {
    self::shrinktheweb_check_scheme_options();
    $result['settings_form'] = \Drupal::formBuilder()->getForm('\Drupal\shrinktheweb\Form\ShrinkTheWebSettingsForm');
    $result['donate_form'] = \Drupal::formBuilder()->getForm('\Drupal\shrinktheweb\Form\ShrinkTheWebDonateForm');
    return $result;
  }
  /**
   * Check https function
   */
  private static function shrinktheweb_is_ssl() {
    if (!empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off') != 0) {
      return TRUE;
    }
    elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
      return TRUE;
    }
    elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') == 0) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check scheme function
   */
  public static function shrinktheweb_check_scheme_options() {
    $config = \Drupal::service('config.factory')->getEditable('shrinktheweb.settings');
    if (self::shrinktheweb_is_ssl()) {
      $config->set('shrinktheweb_enable_https', 1);
      $config->set('shrinktheweb_enable_https_set_automatically', 1);
    }
    else {
      if ($config->get('shrinktheweb_enable_https_set_automatically') == 1) {
        $config->set('shrinktheweb_enable_https', 0);
        $config->set('shrinktheweb_enable_https_set_automatically', 0);
      }
    }
    $config->save();
  }
}