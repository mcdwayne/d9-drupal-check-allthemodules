<?php

namespace Drupal\kong\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Kong settings for this site.
 */
class KongSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kong_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['kong.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['base_uri'] = [
      '#type' => 'url',
      '#title' => $this->t('Base URI'),
      '#default_value' => $this->config('kong.settings')->get('base_uri'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('kong.settings')
      ->set('base_uri', $form_state->getValue('base_uri'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
