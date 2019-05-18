<?php

namespace Drupal\fitbit\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ApplicationSettings.
 *
 * @package Drupal\fitbit\Form
 */
class ApplicationSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fitbit_application_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['fitbit.application_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fitbit.application_settings');

    $instructions = <<<INSTRUCTIONS
<p>In order to communicate with the Fitbit API, you need to create a Fitbit Application, enter in the application parameters below, and have your users connect their Fitbit accounts. Follow these steps:</p>
<ol>
  <li>Visit https://dev.fitbit.com/apps/new and follow the steps to create a new application. If you already have an application, skip to the next step.</li>
  <li>Go to https://dev.fitbit.com/apps and click on the name of your application.</li>
  <li>Copy and paste the OAuth 2.0 Client ID and Client Secret into the fields below.</li>
  <li>Save the settings.</li>
  <li>Instruct your users to visit <em>/user/[uid]/fitbit</em> and follow the steps there to connect their Fitbit accounts.</li>
  <li>At this point you should be able to build views with the Fitbit views module, or otherwise use the services provided if your a module developer basing your code on fitbit module.</li>
</ol>
INSTRUCTIONS;

    $form['instructions'] = [
      '#markup' => $instructions,
    ];

    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OAuth 2.0 Client ID'),
      '#description' => $this->t('Enter the OAuth 2.0 Client ID from your <a href="https://dev.fitbit.com/apps">Fitbit application settings</a>.'),
      '#default_value' => $config->get('client_id'),
    ];
    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client secret'),
      '#description' => $this->t('Enter the Client Secret from your <a href="https://dev.fitbit.com/apps">Fitbit application settings</a>.'),
      '#default_value' => $config->get('client_secret'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('fitbit.application_settings')
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
