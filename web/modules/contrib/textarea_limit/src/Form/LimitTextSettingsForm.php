<?php
/**
 * @file
 * Contains \Drupal\textarea_limit\Form\LimitTextSettingsForm.
 */

namespace Drupal\textarea_limit\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form to configure limit text module.
 */
class LimitTextSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'textarea_limit_text_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['textarea_limit.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('textarea_limit.settings');

    $form['global_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Global limit'),
      '#description' => $this->t('The global character limit for textarea fields that are set to use it.'),
      '#default_value' => empty($config->get('global_limit')) ? '1000' : $config->get('global_limit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('textarea_limit.settings')
      ->set('global_limit', $values['global_limit'])
      ->save();
  }

}
