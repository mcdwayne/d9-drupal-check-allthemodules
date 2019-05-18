<?php

namespace Drupal\bitdash_player\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Defines the global configuration settings form.
 */
class BitdashPlayerSettingForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bitdash_player_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('bitdash_player.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['bitdash_player.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // @FIXME
    // Could not extract the default value because it is either indeterminate,
    // or not scalar. You'll need to provide a default value in
    // config/install/bitdash_player.settings.yml and
    // config/schema/bitdash_player.schema.yml.
    $form['bitdash_player_cdn'] = [
      '#type' => 'textfield',
      '#title' => t('Default CDN URL'),
      '#description' => t('Setup a custom location for bitdash.min.js.'),
      '#default_value' => \Drupal::config('bitdash_player.settings')->get('bitdash_player_custom_cdn'),
      '#required' => TRUE,
    ];
    $form['bitdash_player_api_key'] = [
      '#type' => 'textfield',
      '#title' => t('Bitmovin API key'),
      '#description' => t('Your Bitmovin API key which can be found !link.', [
        '!link' => \Drupal::l(t('here'), Url::fromUri('https://app.bitmovin.com/settings')),
      ]),
      '#default_value' => \Drupal::config('bitdash_player.settings')->get('bitdash_player_api_key'),
      '#required' => TRUE,
    ];
    $form['bitdash_player_player_key'] = [
      '#type' => 'textfield',
      '#title' => t('Bitdash player key'),
      '#description' => t('Your player license key which can be found !link.', [
        '!link' => \Drupal::l(t('here'), Url::fromUri('https://app.bitmovin.com/player/overview')),
      ]),
      '#default_value' => \Drupal::config('bitdash_player.settings')->get('bitdash_player_player_key'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if (!preg_match('/^(https?)?\/\/.*js$/', $values['bitdash_player_cdn'])) {
      $form_state->setErrorByName('bitdash_player_cdn', t('Invalid URL'));
    }
    if (!preg_match('/[a-f0-9]{64}/', $values['bitdash_player_api_key']) || strlen($values['bitdash_player_api_key']) != 64) {
      $form_state->setErrorByName('bitdash_player_api_key', t('Invalid API key'));
    }
    if (!preg_match('/[a-f0-9\-]{36}/', $values['bitdash_player_player_key']) || strlen($values['bitdash_player_player_key']) != 36) {
      $form_state->setErrorByName('bitdash_player_player_key', t('Invalid player licence key'));
    }
  }

}
