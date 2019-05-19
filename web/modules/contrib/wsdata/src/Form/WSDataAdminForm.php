<?php

namespace Drupal\wsdata\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class WSDataAdminForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wsdata_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'wsdata_admin.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $disable = TRUE;
    $config = $this->config('wsdata_admin.settings');

    // Only allow this if the devel module is enabled.
    if (\Drupal::moduleHandler()->moduleExists('kint')) {
      $disable = FALSE;
    }
    else {
      \Drupal::state()->set('wsdata_debug_mode', 0);
    }

    $form['debug_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug Mode'),
      '#description' => $this->t('Devel module must be installed and enabled for this functionality to work.'),
      '#disabled' => $disable,
      '#default_value' => \Drupal::state()->get('wsdata_debug_mode'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    \Drupal::state()->set('wsdata_debug_mode', $values['debug_mode']);
    /**
    $this->config('wsdata_admin.settings')
      ->set('debug_mode', $values['debug_mode'])
      ->save();
      */
    parent::submitForm($form, $form_state);
  }
}
