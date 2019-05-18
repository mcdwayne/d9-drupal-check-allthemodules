<?php
/**
 * @file
 * Contains \Drupal\openlayers\Form\LayerOptionsSettingsForm.
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
class LayerOptionsSettingsForm extends FormBase {
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'openlayers_layeroptions_settings';
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
    $form['openlayers_layeroptions_settings']['#markup'] = 'Settings form for OpenLayers layeroptions. Manage field settings here.';
    return $form;
  }
}
