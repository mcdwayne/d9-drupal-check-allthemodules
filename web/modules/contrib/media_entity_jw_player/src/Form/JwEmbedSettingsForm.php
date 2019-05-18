<?php

namespace Drupal\media_entity_jw_player\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\core\Config;

/**
 * Presents the module settings form.
 */
class JwEmbedSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_entity_jw_player_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['media_entity_jw_player.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('media_entity_jw_player.settings');

    $form['jw_authKey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('JW Player Authentication ID'),
      '#default_value' => $config->get('jw_authKey', 'xxxxxx'),
      '#required' => TRUE,
    ];

    $form['jw_expiration_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Expiration time in seconds'),
      '#default_value' => $config->get('jw_expiration_time', 0),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('media_entity_jw_player.settings');
    $config->set('jw_authKey', $form_state->getValue('jw_authKey'))->save();
    $config->set('jw_expiration_time', $form_state->getValue('jw_expiration_time'))->save();
    parent::submitForm($form, $form_state);
  }

}
