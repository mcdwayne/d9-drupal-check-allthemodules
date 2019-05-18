<?php

namespace Drupal\account_modal\Form;

use Drupal\account_modal\AccountPageHelper;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The admin settings form for Account Modal.
 */
class AccountModalSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'account_modal_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('account_modal.settings')
      ->set('enabled_pages', $form_state->getValue('enabled_pages'))
      ->set('hide_field_descriptions', $form_state->getValue('hide_field_descriptions'))
      ->set('reload_on_success', $form_state->getValue('reload_on_success'))
      ->set('dialog_width', $form_state->getValue('dialog_width'))
      ->set('dialog_height', $form_state->getValue('dialog_height'))
      ->set('create_profile_after_registration', $form_state->getValue('create_profile_after_registration'))
      ->set('profile_type', $form_state->getValue('profile_type'))
      ->set('header_blocks', $form_state->getValue('header_blocks'))
      ->set('footer_blocks', $form_state->getValue('footer_blocks'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['account_modal.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('account_modal.settings');
    $accountPageHelper = new AccountPageHelper();

    $form['enabled_pages'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled pages'),
      '#description' => $this->t('Select the account pages to show in a modal window.'),
      '#options' => $accountPageHelper->getPageOptions(),
      '#default_value' => $config->get('enabled_pages'),
    ];

    $form['hide_field_descriptions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide field descriptions'),
      '#description' => $this->t('Remove field descriptions from forms in the modal.'),
      '#default_value' => $config->get('hide_field_descriptions'),
    ];

    $form['reload_on_success'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reload on success'),
      '#description' => $this->t('Reload the page (instead of redirecting) upon completion.'),
      '#default_value' => $config->get('reload_on_success'),
    ];

    $form['dialog_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dialog width'),
      '#description' => $this->t('The value should either be the width of the modal window in pixels, or "auto". The default is 480.'),
      '#default_value' => $config->get('dialog_width'),
    ];

    $form['dialog_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dialog height'),
      '#description' => $this->t('The value should either be the height of the modal window in pixels, or "auto". The default is auto.'),
      '#default_value' => $config->get('dialog_height'),
    ];

    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler */
    $moduleHandler = \Drupal::service('module_handler');
    $profileIsInstalled = $moduleHandler->moduleExists('profile');

    $form['create_profile_after_registration'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create profile after registration'),
      '#description' => $this->t('Optionally, show a form to create a new profile after registration. Requires the Profile module.'),
      '#disabled' => !$profileIsInstalled,
      '#default_value' => $profileIsInstalled ? $config->get('create_profile_after_registration') : FALSE,
    ];

    $form['profile_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Profile type'),
      '#description' => $this->t('If creating a profile, enter the bundle to create.'),
      '#disabled' => !$profileIsInstalled,
      '#default_value' => $config->get('profile_type'),
    ];

    $form['header_blocks'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Header blocks'),
      '#description' => $this->t('A list of block IDs, one per line, to render in the dialog header.'),
      '#default_value' => $config->get('header_blocks'),
    ];

    $form['footer_blocks'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Footer blocks'),
      '#description' => $this->t('A list of block IDs, one per line, to render in the dialog footer.'),
      '#default_value' => $config->get('footer_blocks'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
