<?php

namespace Drupal\user_agent_class\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ProvideForm.
 */
class ProvideForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'user_agent_class.provide',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'provide_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('user_agent_class.provide');
    $form['responsibility_frontend_backend'] = [
      '#type' => 'radios',
      '#title' => $this->t('Responsibility Frontend/Backend'),
      '#description' => $this->t('Please choice how do you want to get user agent information. <br> * You need to disable "internal page cache" module and only keep "dynamic page cache" enabled.'),
      '#options' => [
        '0' => $this->t('Frontend(js)'),
        '1' => $this->t('Backend(php)'),
      ],
      '#default_value' => $config->get('user_agent_class.responsibility_frontend_backend'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('user_agent_class.provide')
      ->set('user_agent_class.responsibility_frontend_backend', $form_state->getValue('responsibility_frontend_backend'))
      ->save();
  }

}
