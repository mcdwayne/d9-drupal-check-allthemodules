<?php

namespace Drupal\no_admin_destination_redirect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class NoAdminRedirectConfigForm.
 */
class NoAdminRedirectConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'no_admin_destination_redirect.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'no_admin_redirect_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('no_admin_destination_redirect.settings');
    $form['excluded_paths_config'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Excluded paths (one per line, no regex or wildcards please).'),
      '#default_value' => $config->get('excluded_paths_config'),
      '#description' => 'E.g. "/admin/config" You may need to flush cache after changing this setting.',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('no_admin_destination_redirect.settings')
      ->set('excluded_paths_config', $form_state->getValue('excluded_paths_config'))
      ->save();
  }

}
