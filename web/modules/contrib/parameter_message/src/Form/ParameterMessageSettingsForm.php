<?php

namespace Drupal\parameter_message\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for configure messages.
 */
class ParameterMessageSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'parameter_message_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'parameter_message.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('parameter_message.settings');

    $form['parameter_message_messages'] = [
      '#type' => 'textarea',
      '#default_value' => $config->get('messages'),
      '#title' => $this->t('Messages and parameters'),
      '#description' => $this->t('Insert values with format: <br><b>parameter=value|Message to show|type</b><br> e.g. <br><b>example=success|Example of success|status</b>'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $array = array_values(array_filter(explode(PHP_EOL, str_replace("\r", '', $_POST['parameter_message_messages']))));
    $parameter_message_messages = implode(PHP_EOL, $array);

    $config = $this->config('parameter_message.settings');
    $config->set('messages', $parameter_message_messages);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
