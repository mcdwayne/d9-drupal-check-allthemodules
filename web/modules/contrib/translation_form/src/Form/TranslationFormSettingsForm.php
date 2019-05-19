<?php

namespace Drupal\translation_form\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TranslationFormSettingsForm.
 *
 * @package Drupal\translation_form\Form
 */
class TranslationFormSettingsForm extends ConfigFormBase {

  /**
   * Config name.
   *
   * @var string
   */
  protected static $configName = 'translation_form.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [static::$configName];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'translation_form_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::$configName);
    $form['always_display_original_language_translation'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Always display original language translation'),
      '#default_value' => $config->get('always_display_original_language_translation'),
    ];
    $form['hide_languages_without_translation'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Hide languages without translation'),
      '#default_value' => $config->get('hide_languages_without_translation'),
    ];
    $form['allow_to_change_source_language'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Allow to change the source language when editing translations'),
      '#default_value' => $config->get('allow_to_change_source_language'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $editable = $this->configFactory()->getEditable(static::$configName);
    $editable
      ->set(
        'always_display_original_language_translation',
        $form_state->getValue('always_display_original_language_translation'))
      ->set(
        'hide_languages_without_translation',
        $form_state->getValue('hide_languages_without_translation'))
      ->set(
        'allow_to_change_source_language',
        $form_state->getValue('allow_to_change_source_language'))
      ->save(TRUE);
    parent::submitForm($form, $form_state);
  }

}
