<?php

/**
 * @file
 * This is the Slimbox Module Settings Form
 * Contains \Drupal\slimbox\Form\slimboxSettingsForm.
 */

namespace Drupal\slimbox2\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Slimbox2SettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'slimbox2_settings';
  }
}
