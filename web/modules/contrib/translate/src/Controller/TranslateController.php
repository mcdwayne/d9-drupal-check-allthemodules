<?php

namespace Drupal\translate\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * @class
 * TranslateController.
 */
class TranslateController extends ControllerBase {

  /**
   * Define module urls.
   */
  public function content() {

    $configs = \Drupal::config("translate.settings");
    $embed_code = $configs->get("embed_code");
    if (isset($embed_code)) {
      // Set is_registered yes so that we don't display registration form.
      $script = "<script>localStorage.setItem('is_registered', 'yes');</script>";
    }
    else {
      // Remove is_registered variable.
      $script = "<script>localStorage.removeItem('is_registered');</script>";
    }

    // Show form for site registration.
    $iframe = '<iframe style="border:none;" src="/modules/translate/settings.html" width="100%" height="1100px"></iframe>';
    $html = $script . $iframe;

    return [
      '#type' => 'markup',
      '#markup' => t($html),
    ];
  }

  /**
   * Save embed_code for this site in drupal configs.
   */
  public function embed() {
    $embed_code = \Drupal::request()->query->get('embed_code');
    \Drupal::getContainer()->get('config.factory')->getEditable("translate.settings")
      ->set('embed_code', $embed_code)
      ->save();
    return [];
  }

  /**
   * Remove embed_code for this site in drupal configs.
   */
  public function signout() {
    $config_factory = \Drupal::configFactory();
    // Delete embed_code from configs.
    $config_factory->getEditable('translate.settings')->delete();
    return [];
  }

}
