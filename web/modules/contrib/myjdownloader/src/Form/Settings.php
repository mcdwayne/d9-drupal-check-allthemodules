<?php

namespace Drupal\myjdownloader\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\myjdownloader\MyJdHelper;

/**
 * My jDownloader settings from.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'myjdownloader_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [MyJdHelper::getConfigName()];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = MyJdHelper::getConfig();

    $form['email'] = [
      '#type' => 'textfield',
      '#title' => 'Email',
      '#required' => TRUE,
      '#default_value' => $config->get('email'),
    ];

    $form['password'] = [
      '#type' => 'password',
      '#title' => 'Password',
    ];

    $form['device'] = [
      '#type' => 'textfield',
      '#title' => 'Device name',
      '#required' => TRUE,
      '#default_value' => $config->get('device'),
    ];

    $form['folder'] = [
      '#type' => 'textfield',
      '#title' => 'Destination folder',
      '#default_value' => $config->get('folder'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => "Save Settings",
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $list = [
      'email',
      'device',
    ];

    foreach ($list as $item) {
      if (!$form_state->getValue($item)) {
        $form_state->setErrorByName($item, "Field $item is empty");
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $list = [
      'email',
      'password',
      'device',
      'folder',
    ];
    $config = $this->config(MyJdHelper::getConfigName());
    foreach ($list as $item) {
      if ($value = $form_state->getValue($item)) {
        $config->set($item, $value);
      }
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
