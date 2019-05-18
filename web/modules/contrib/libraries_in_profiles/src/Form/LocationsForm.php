<?php

namespace Drupal\libraries_in_profiles\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LocationsForm.
 *
 * @package Drupal\libraries_in_profiles\Form
 */
class LocationsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'libraries_in_profiles.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'locations_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('libraries_in_profiles.settings');
    $form['extra_library_search_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extra Library Search Location'),
      '#description' => $this->t('An alternative location that css or js files may be located.'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('location'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $location = $form_state->getValue('extra_library_search_location');
    if ($location && substr($location, -1) != '/') {
      $location .= '/';
    }
    $this->config('libraries_in_profiles.settings')
      ->set('location', $location)
      ->save();
  }

}
