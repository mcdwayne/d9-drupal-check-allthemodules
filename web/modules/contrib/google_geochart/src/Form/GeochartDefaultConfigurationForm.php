<?php

namespace Drupal\google_geochart\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class GeochartDefaultConfigurationForm.
 */
class GeochartDefaultConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'google_geochart.geochartdefaultconfiguration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'geochart_default_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_geochart.geochartdefaultconfiguration');
    $form['google_maps_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google maps api key'),
      '#description' => $this->t('For generating google maps api key,  See https://developers.google.com/chart/interactive/docs/basic_load_libs#load-settings'),
      '#maxlength' => 256,
      '#size' => 64,
      '#default_value' => $config->get('google_maps_api_key'),
    ];
    $form['default_google_visualization_arr'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default data (Json encoded array)'),
      '#description' => $this->t('Google visualization array to DataTable, Json encoded array Example like ==> "[[\"Country\",\"Popularity\"],[\"Germany\",200],[\"United States\",300],[\"Brazil\",400],[\"Canada\",500],[\"France\",600],[\"RU\",700]]" '),
      '#default_value' => $config->get('default_google_visualization_arr'),
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

    $this->config('google_geochart.geochartdefaultconfiguration')
      ->set('google_maps_api_key', $form_state->getValue('google_maps_api_key'))
      ->set('default_google_visualization_arr', $form_state->getValue('default_google_visualization_arr'))
      ->save();
  }

}
