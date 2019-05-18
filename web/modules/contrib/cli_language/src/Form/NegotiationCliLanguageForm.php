<?php

namespace Drupal\cli_language\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure the CLI language negotiation method for this site.
 */
class NegotiationCliLanguageForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'language_negotiation_configure_cli_language_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cli_language.negotiation'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cli_language.negotiation');
    $form['language_code'] = [
      '#type' => 'language_select',
      '#title' => $this->t('Language'),
      '#languages' => LanguageInterface::STATE_CONFIGURABLE | LanguageInterface::STATE_SITE_DEFAULT,
      '#default_value' => $config->get('language_code'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('cli_language.negotiation')
      ->set('language_code', $form_state->getValue('language_code'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
