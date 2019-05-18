<?php
/**
 * @file
 * Contains \Drupal\openlayers\Form\LayerSettingsForm.
 */

namespace Drupal\openlayers\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ContentEntityExampleSettingsForm.
 *
 * @package Drupal\openlayers\Form
 *
 * @ingroup openlayers
 */
class LayerSettingsForm extends FormBase {
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'openlayers_layer_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['openlayers_layer_settings']['#markup'] = 'Settings form for OpenLayers layer. Manage field settings here.';
    return $form;
  }
}
