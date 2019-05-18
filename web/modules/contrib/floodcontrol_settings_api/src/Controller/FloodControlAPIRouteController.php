<?php

namespace Drupal\floodcontrol_settings_api\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for User module routes.
 */
class FloodControlAPIRouteController extends ControllerBase {

  /**
   * Returns the route content.
   *
   * @return array
   *   A renderable array containing the page content.
   */
  public function index() {
    $output = [
      'intro' => [
        '#type' => 'item',
        '#title' => t('Flood Control API - Settings'),
        '#markup' => 'If floodcontrol_api_settings API is implemented in your custom module, '
        . 'then your custom form settings appear here. Further details given in README.txt',
      ],
      'settings_form' => \Drupal::formBuilder()->getForm('Drupal\floodcontrol_settings_api\Form\SettingsForm'),
    ];
    return $output;
  }

}
