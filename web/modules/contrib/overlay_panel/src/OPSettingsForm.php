<?php

namespace Drupal\overlay_panel;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure overlay_panel settings for this site.
 *
 * @internal
 */
class OPSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'op_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['overlay_panel.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('overlay_panel.settings');

    $form['most_viewed_user_types'] = [
      '#type' => 'checkboxes',
      '#title' => t('User types'),
      '#description' => t('The user bundles that should be watched.'),
      '#options' => ['user' => t('User')],
      '#default_value' => $config->get('most_viewed_user_types'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('overlay_panel.settings')
      ->set('most_viewed_user_types', $form_state->getValue('most_viewed_user_types'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
