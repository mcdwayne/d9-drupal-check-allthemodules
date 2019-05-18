<?php

namespace Drupal\openweather\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Contribute form.
 */
class PassConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openweather_appid_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['openweather.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['appid'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('App Id'),
      '#required' => TRUE,
      '#default_value' => $this->config('openweather.settings')->get('appid'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('openweather.settings')
      ->set('appid', $form_state->getValue('appid'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
