<?php

namespace Drupal\cami\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure cami settings.
 */
class CamiSettingsForm extends ConfigFormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cami_settings';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cami.settings',
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cami.settings');
    $form['li_classes'] = array(
      '#type' => 'textfield',
      '#title' => t('List classes'),
      '#description' => t('Classes applied to <strong>li</strong> element (separate classes with spaces)'),
      '#default_value' => $config->get('li_classes'),
    );
    $form['a_classes'] = array(
      '#type' => 'textfield',
      '#title' => t('Link classes'),
      '#description' => t('Classes applied to <strong>a</strong> element (separate classes with spaces)'),
      '#default_value' => $config->get('a_classes'),
    );
    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('cami.settings')
      ->set('li_classes', $form_state->getValue('li_classes'))
      ->set('a_classes', $form_state->getValue('a_classes'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}