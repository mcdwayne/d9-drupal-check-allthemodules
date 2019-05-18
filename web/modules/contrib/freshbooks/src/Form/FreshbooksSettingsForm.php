<?php
/**
 * (c) MagnaX Software
 */

namespace Drupal\freshbooks\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class FreshbooksSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'freshbooks_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'freshbooks.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('freshbooks.settings');

    $form['freshbooks_domain'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('API Domain'),
      '#default_value' => $config->get('domain'),
    );
    $form['freshbooks_token'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('API Authentication Token'),
      '#default_value' => $config->get('token'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('freshbooks.settings')
      ->set('domain', trim($form_state->getValue('freshbooks_domain')))
      ->set('token', trim($form_state->getValue('freshbooks_token')))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
