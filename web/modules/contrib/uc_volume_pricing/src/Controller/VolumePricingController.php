<?php

namespace Drupal\uc_volume_pricing\Controller;

/**
 * @file
 * Contains \Drupal\uc_volume_pricing\Controller\VolumePricingController.
 */

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for Volume price settings form.
 */
class VolumePricingController extends ControllerBase {

  /**
   * Renders the form for the Volume pricing message settings page.
   */
  public function volumePricingSettings() {

    $build = array();
    $build['volume_pricing_settings_form'] = $this->formBuilder()->getForm('Drupal\uc_volume_pricing\Form\SettingsForm');
    return $build;
  }

}
