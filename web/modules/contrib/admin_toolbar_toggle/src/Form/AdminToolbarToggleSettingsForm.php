<?php

namespace Drupal\admin_toolbar_toggle\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays the Admin Toolbar Toggle settings form.
 */
class AdminToolbarToggleSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['admin_toolbar_toggle.settings'];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormId() {
    return 'admin_toolbar_toggle_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('admin_toolbar_toggle.settings');

    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Keyboard hotkey'),
      '#description' => $this->t('The keyboard hotkey that will toggle the Admin Toolbar visibility.'),
      '#default_value' => $config->get('key'),
      '#maxlength' => 1,
    ];

    // Submit button.
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('admin_toolbar_toggle.settings');
    $config->set('key', $form_state->getValue('key'));
    $config->save();
    drupal_set_message($this->t('The Admin Toolbar Toggle settings have been saved.'), 'status');

    parent::submitForm($form, $form_state);
  }

}
