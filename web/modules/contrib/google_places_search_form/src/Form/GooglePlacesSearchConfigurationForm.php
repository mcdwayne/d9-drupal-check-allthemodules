<?php

namespace Drupal\google_places_search_form\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines configuration form for google places search form.
 */
class GooglePlacesSearchConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_places_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'google_places_search_form.admin_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_places_search_form.admin_settings');
    $form['destination_page_path'] = [
      '#type' => 'url',
      '#title' => $this->t('Destination page link'),
      '#default_value' => $config->get('destination_page_path'),
      '#description' => $this->t('Enter the url, to which you want to redirect the 
      form to. Or the page url of the view with proximity search as 
      contextual filter.'),
    ];
    $form['google_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Api Key'),
      '#default_value' => $config->get('google_api_key'),
      '#description' => $this->t('Enter the google api key.'),
    ];
    $form['show_distance_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show/Hide Distance field'),
      '#default_value' => $config->get('show_distance_field'),
      '#description' => $this->t('Check to show distance field in the search form.'),
      '#prefix' => $this->t('<label>Distance field</label>'),
    ];
    $form['distance_parameter'] = [
      '#type' => 'radios',
      '#title' => $this->t('Distance in:'),
      '#options' => ['km' => $this->t('Km'), 'miles' => $this->t('Miles')],
      '#default_value' => NULL == $config->get('distance_parameter') ? 'km' : $config->get('distance_parameter'),
      '#description' => $this->t('Check to show distance field in the search form.'),
      '#states' => [
        'invisible' => [
          ':input[name="show_distance_field"]' => ['checked' => FALSE],
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('google_places_search_form.admin_settings')
      ->set('destination_page_path', $values['destination_page_path'])
      ->set('google_api_key', $values['google_api_key'])
      ->set('show_distance_field', $values['show_distance_field'])
      ->set('distance_parameter', $values['distance_parameter'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
