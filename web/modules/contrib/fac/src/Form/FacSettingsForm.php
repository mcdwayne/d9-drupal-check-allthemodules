<?php

namespace Drupal\fac\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class FacSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fac_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'fac.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fac.settings');

    $form['key_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Key update interval'),
      '#default_value' => $config->get('key_interval'),
      '#description' => $this->t('To reduce the risk of information leakage a role-based hash/key is used in the json files URL. Enter the key update interval in seconds (defaults to one week).'),
    ];

    $form['highlighting_script_use_cdn'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use a CDN to load the highlighting script'),
      '#default_value' => $config->get('highlighting_script_use_cdn'),
      '#description' => $this->t('Disable this option to use a local version of the mark.js script. <a href="@url">Download the script</a> and save it in the /libraries/mark.js/ folder in your codebase.', [
        '@url' => 'https://cdnjs.cloudflare.com/ajax/libs/mark.js/8.11.1/jquery.mark.min.js',
      ]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('fac.settings')
      ->set('key_interval', $form_state->getValue('key_interval'))
      ->set('highlighting_script_use_cdn', $form_state->getValue('highlighting_script_use_cdn'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
