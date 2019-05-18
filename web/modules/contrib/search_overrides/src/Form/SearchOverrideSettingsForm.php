<?php

namespace Drupal\search_overrides\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SearchElevateSettingsForm.
 *
 * @ingroup search_api_solr_elevate_exclude
 */
class SearchOverrideSettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'searchoverride_settings';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('searchoverride_settings.settings');
    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'search_override_') !== FALSE) {
        $config->set(str_replace('search_override_', '', $key), $value);
      }
    }
    $config->save();
    drupal_set_message($this->t('Configuration was saved.'));
  }

  /**
   * Defines the settings form for Search elevate entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['searchoverride_settings']['#markup'] = 'Settings form for Search overrides. Manage configuration here.';
    $config = $this->config('searchoverride_settings.settings');
    $form['search_override_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search Path'),
      '#default_value' => $config->get('path'),
      '#placeholder' => '/search',
      '#description' => $this->t('The site-relative path to the search results page. Be sure to include the preceding slash'),
      '#required' => TRUE,
    ];
    $form['search_override_parameter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL Parameter'),
      '#placeholder' => 'query',
      '#default_value' => $config->get('parameter'),
      '#description' => $this->t('The URL parameter through which search keywords are passed'),
      '#required' => TRUE,
    ];
    $form['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    return $form;
  }

}
