<?php

namespace Drupal\fz152\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class Fz152SettingsPage extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fz152_page_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'fz152.settings',
      'fz152.privacy_policy_page'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fz152.privacy_policy_page');
    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable privacy policy page'),
      '#default_value' => $config->get('enable'),
    ];
    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Privacy policy page path'),
      '#default_value' => $config->get('path'),
    ];
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Privacy policy page title'),
      '#default_value' => $config->get('title'),
    ];
    $text = $config->get('text');
    $default_text = $text['value'];
    $default_format = isset($text['format']) ? $text['format'] : filter_default_format();

    $form['text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Privacy policy text'),
      '#default_value' => $default_text,
      '#format' => $default_format,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('fz152.privacy_policy_page')
      ->set('enable', $form_state->getValue('enabled'))
      ->set('path', $form_state->getValue('path'))
      ->set('title', $form_state->getValue('title'))
      ->set('text', $form_state->getValue('text'))
      ->save();

    // Rebuilding the menu router cache to register routes for settings forms.
    \Drupal::service('router.builder')->rebuild();
    drupal_set_message($this->t('Menu router cache data rebuilded.'));

    parent::submitForm($form, $form_state);
  }

}
