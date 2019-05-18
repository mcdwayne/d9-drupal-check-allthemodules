<?php

namespace Drupal\private_entity\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'private_entity.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('private_entity.settings');
    $form['messages'] = [
      '#type' => 'fieldset',
      '#title' => t('Confirmation message'),
      '#description' => t('Confirmation of the access status after saving an entity.'),
      '#collapsible' => TRUE,
    ];
    $form['messages']['confirm_public'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('When the entity is <strong>public</strong>'),
      '#default_value' => $config->get('confirm_public'),
    ];
    $form['messages']['confirm_private'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('When the entity is <strong>private</strong>'),
      '#default_value' => $config->get('confirm_private'),
    ];
    $form['redirection'] = [
      '#type' => 'fieldset',
      '#title' => t('Redirection'),
      '#collapsible' => TRUE,
    ];
    $form['redirection']['user_login_redirect'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Redirect to the user login [WIP]'),
      '#description' => $this->t('Redirect to the user login with the destination when the entity is private.'),
      '#default_value' => $config->get('user_login_redirect'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('private_entity.settings')
      ->set('confirm_public', $form_state->getValue('confirm_public'))
      ->set('confirm_private', $form_state->getValue('confirm_private'))
      ->set('user_login_redirect', $form_state->getValue('user_login_redirect'))
      ->save();
  }

}
