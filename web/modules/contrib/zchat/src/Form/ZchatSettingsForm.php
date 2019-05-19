<?php

namespace Drupal\zchat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure zchat settings for this site.
 */
class ZchatSettingsForm extends ConfigFormBase {
  const SETTINGS = 'zchat.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'zchat_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['zchat_include_default_css'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include default CSS (needed for loading on scroll, remove this on your own responsibility)'),
      '#default_value' => $config->get('zchat_include_default_css'),
    ];

    $form['zchat_include_style_css'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include style CSS'),
      '#default_value' => $config->get('zchat_include_style_css'),
    ];

    $form['zchat_message_refresh_interval'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Interval to look for new messages (milliseconds)'),
      '#default_value' => $config->get('zchat_message_refresh_interval'),
    ];

    $form['zchat_load_more_offeset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Offset for load more on scrolling (pixels)'),
      '#default_value' => $config->get('zchat_load_more_offeset'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
    // Set the submitted configuration settings.
      ->set('zchat_include_default_css', $form_state->getValue('zchat_include_default_css'))
      ->set('zchat_include_style_css', $form_state->getValue('zchat_include_style_css'))
      ->set('zchat_message_refresh_interval', $form_state->getValue('zchat_message_refresh_interval'))
      ->set('zchat_load_more_offeset', $form_state->getValue('zchat_load_more_offeset'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
