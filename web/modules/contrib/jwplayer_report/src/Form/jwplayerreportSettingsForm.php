<?php

namespace Drupal\jwplayer_report\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class jwplayerreportSettingsForm extends ConfigFormBase
{
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'jwplayerreport_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return [
    'jwplayer_report.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('jwplayer_report.settings');

    $form['property_name_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t("Property Name Key"),
      '#default_value' => $config->get('property_name_key'),
      '#required' => TRUE,
      '#description' => $this->t("Enter Property Name Key.")
    );

    $form['api_secret_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t("API Secret Key"),
      '#default_value' => $config->get('api_secret_key'),
      '#required' => TRUE,
      '#description' => $this->t("Enter API Secret Key.")
    );

 
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // Retrieve the configuration
    $this->config('jwplayer_report.settings')
    // Set the submitted configuration setting
    ->set('property_name_key', $form_state->getValue('property_name_key'))
    ->set('api_secret_key', $form_state->getValue('api_secret_key'))
    ->save();

    parent::submitForm($form, $form_state);
  }
}



