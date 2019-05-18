<?php
/**
 * @file
 * Contains \Drupal\openlayers\Form\MapSettingsForm.
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
class MapSettingsForm extends FormBase {
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'openlayers_map_settings';
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
    $form['openlayers_map_settings']['#markup'] = 'Settings form for OpenLayers map. Manage field settings here.';
    return $form;
  }
}
