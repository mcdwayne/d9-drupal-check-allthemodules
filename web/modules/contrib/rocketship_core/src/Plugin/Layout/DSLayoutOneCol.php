<?php

namespace Drupal\rocketship_core\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Class DSLayoutOneCol.
 *
 * @package Drupal\rocketship_core\Plugin\Layout
 */
class DSLayoutOneCol extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // parent::submitConfigurationForm($form, $form_state);.
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

}
