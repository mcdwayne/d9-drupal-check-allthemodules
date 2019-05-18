<?php

namespace Drupal\js\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class JsConfigureForm.
 */
class JsConfigureForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'js_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['js.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('js.settings');

    // Endpoint.
    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('JS Callback Endpoint'),
      '#description' => $this->t('The endpoint used for all JS Callback requests.', [
        '@endpoint' => $form_state->getValue('endpoint', $this->config('js.settings')->get('endpoint')),
      ]),
      '#default_value' => $form_state->getValue('endpoint', $config->get('endpoint')),
    ];

    // Silence PHP Errors.
    $form['silence_php_errors'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Silence PHP Errors'),
      '#description' => $this->t('Prevents custom JS Callback Handler PHP error and exception handlers from being invoked.'),
      '#default_value' => $form_state->getValue('silence_php_errors', $config->get('silence_php_errors')),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('js.settings');
    $config->set('endpoint', $form_state->getValue('endpoint') ?: '/js');
    $config->set('silence_php_errors', $form_state->getValue('silence_php_errors') ?: FALSE);
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
