<?php

namespace Drupal\random_captcha_type\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Random CAPTCHA type settings form.
 */
class RandomCaptchaTypeSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'random_captcha_type_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['random_captcha_type.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $enabled_types = _random_captcha_type_get_enabled_types();
    if (count($enabled_types) < 2) {
      drupal_set_message($this->t('You need at least two CAPTCHA types (other than %random_captcha_type).', ['%random_captcha_type' => 'Random CAPTCHA type']), 'error');
    }
    $form = [];
    $captcha_types = _random_captcha_type_get_all_types();
    $form['random_captcha_type_enabled_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Randomly switch between'),
      '#options' => $captcha_types,
      '#default_value' => $enabled_types,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('random_captcha_type.settings')
      ->set('random_captcha_type_enabled_types', $form_state->getValue('random_captcha_type_enabled_types'))
      ->save();

    parent::SubmitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // At least 2 CAPTCHA types should be selected.
    if (count(array_filter($form_state->getValue('random_captcha_type_enabled_types'))) < 2) {
      $form_state->setErrorByName('random_captcha_type_enabled_types', $this->t('You should select at least two CAPTCHA types.'));
    }

    parent::validateForm($form, $form_state);
  }

}
