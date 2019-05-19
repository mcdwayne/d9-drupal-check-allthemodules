<?php

/**
 * @file
 * Contains \Drupal\demo\Form\DemoForm.
 */

namespace Drupal\szube_api\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\szube_api\SzuBeAPIHelper;


class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'szube_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [SzuBeAPIHelper::getConfigName()];
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = SzuBeAPIHelper::getConfig();


    $form['apiid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SzuBe API ID'),
      '#default_value' => $config->get('apiid'),
      '#description' => $this->t('Your ID generated from SzuBe (https://szu.be/szu/apiconfig)'),
    ];

    $form['apikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SzuBe API Key'),
      '#default_value' => $config->get('apikey'),
      '#description' => $this->t('Your Key generated from SzuBe (https://szu.be/szu/apiconfig)'),
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

    if (!$form_state->getValue('apikey')) {
      $form_state->setErrorByName('apikey', "API Key is empty");
    }
    if (!$form_state->getValue('apiid')) {
      $form_state->setErrorByName('apiid', "API ID is empty");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = SzuBeAPIHelper::getConfig(TRUE);

    $config->set('apikey', $form_state->getValue('apikey'))
      ->set('apiid', $form_state->getValue('apiid'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
