<?php

namespace Drupal\altruja\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\altruja\AltrujaAPI;

/**
 * The Altruja settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'altruja_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    // Default settings.
    $config = $this->config('altruja.settings');

    // Page title field.
    $form['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your Altruja email address:'),
      '#default_value' => $config->get('altruja.email'),
      '#description' => $this->t('Enter the email address that you use to access your <a href=":url_myaltruja">MyAltruja</a>, or create a <a href=":url_new_account">new account</a>.', [
        ':url_myaltruja' => 'https://www.altruja.de/myaltruja',
        ':url_new_account' => 'https://www.altruja.de/register',
      ]),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $response = AltrujaAPI::queryEndpoint('email/' . urlencode($form_state->getValue('email')));
    if (empty($response->status) || $response->status != 'success') {
      $message = $this->t('The validation API call to Altruja failed. Please review the email address. If the problem persists, please contact the site administrator or the Altruja support.');
      if (!empty($response->message)) {
        $message .= "\n" . $this->t('The Altruja API response was: @message', [
          '@message' => $response->message,
        ]);
      }
      $form_state->setErrorByName('email', $message);
    }
    else {
      $form_state->setTemporaryValue('altruja_response', $response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('altruja.settings');
    $altruja_response = $form_state->getTemporaryValue('altruja_response');
    $src_url = explode('/', $altruja_response->src);
    $client_code = $src_url[count($src_url) - 1];

    $config->set('altruja.email', $form_state->getValue('email'));
    $config->set('altruja.client_code', $client_code);
    $config->set('altruja.link', $altruja_response->link);
    $config->set('altruja.src', $altruja_response->src);
    $config->set('altruja.async', $altruja_response->async);
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'altruja.settings',
    ];
  }

}
