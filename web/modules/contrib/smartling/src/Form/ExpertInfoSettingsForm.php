<?php

/**
 * @file
 * Contains \Drupal\smartling\Form\ExpertInfoSettingsForm.
 */

namespace Drupal\smartling\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Smartling expert settings form.
 */
class ExpertInfoSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'smartling.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smartling_expert_info_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('smartling.settings');
    $form['log_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Smartling log'),
      '#default_value' => $config->get('expert.log_mode'),
      '#description' => $this->t('Log ON dy default.'),
    ];

    $form['async_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Asynchronous mode'),
      '#description' => $this->t('If you uncheck this, the Smartling Connector will attempt to submit content immediately to Smartling servers.'),
      '#default_value' => $config->get('expert.async_mode'),
    ];

    $form['convert_entities_before_translation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Convert entities before translation'),
      '#description' => $this->t('If this is unchecked, then you should convert your content manually from "language-neutral" to default language (usually english) before sending content item for translation.'),
      '#default_value' => $config->get('expert.convert_entities_before_translation'),
    ];

    $form['ui_translations_merge_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('UI translation mode'),
      '#description' => $this->t('If checked: Translation import mode keeping existing translations and only inserting new strings, strings overwrite happens otherwise.'),
      '#default_value' => $config->get('expert.ui_translations_merge_mode'),
    ];

    $form['custom_regexp_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom RegExp for placeholder'),
      '#description' => $this->t('The content that matches this regular expression will be replaced before translation in Smartling dashboard.'),
      '#default_value' => $config->get('expert.custom_regexp_placeholder'),
    ];

    $form['xml_comments'] = [
      '#type' => 'textarea',
      '#title' => $this->t('XML comments to teach smartling to parse source files'),
      '#default_value' => $config->get('expert.xml_comments'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('smartling.settings')
      ->set('expert.log_mode', $form_state->getValue('log_mode'))
      ->set('expert.async_mode', $form_state->getValue('async_mode'))
      ->set('expert.convert_entities_before_translation', $form_state->getValue('convert_entities_before_translation'))
      ->set('expert.ui_translations_merge_mode', $form_state->getValue('ui_translations_merge_mode'))
      ->set('expert.custom_regexp_placeholder', $form_state->getValue('custom_regexp_placeholder'))
      ->set('expert.xml_comments', $form_state->getValue('xml_comments'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
