<?php

namespace Drupal\js_disclaimer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * JS Disclaimer message configuration form.
 *
 * Lets the user set disclaimer message from administration section.
 */
class JsDisclaimerForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {

    return 'js_disclaimer_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);
    $config = $this->config('js_disclaimer.settings');

    $form['disclaimer_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Disclaimer message:'),
      '#default_value' => $config->get('js_disclaimer.disclaimer_message'),
      '#description' => $this->t('The message to display on the Disclaimer Pop-up for external links.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('js_disclaimer.settings');
    $config->set('js_disclaimer.disclaimer_message', $form_state->getValue('disclaimer_message'));
    $config->save();

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {

    return [
      'js_disclaimer.settings',
    ];
  }

}
