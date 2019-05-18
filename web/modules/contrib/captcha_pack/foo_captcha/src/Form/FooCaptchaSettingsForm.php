<?php

namespace Drupal\foo_captcha\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Foo CAPTCHA settings form.
 */
class FooCaptchaSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'foo_captcha_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['foo_captcha.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('foo_captcha.settings');

    $form = [];
    $form['foo_captcha_ignore_spaces'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore spaces in the response'),
      '#default_value' => $config->get('foo_captcha_ignore_spaces'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('foo_captcha.settings')
      ->set('foo_captcha_ignore_spaces', $form_state->getValue('foo_captcha_ignore_spaces'))
      ->save();

    parent::SubmitForm($form, $form_state);
  }

}
