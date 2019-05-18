<?php

namespace Drupal\block_cookies\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConfigForm.
 */
class BlockCookiesSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['block_cookies.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_cookies_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('block_cookies.settings');

    $form['force_allow_cookies_auth_users'] = [
      '#type' => 'radios',
      '#title' => $this->t('Force cookies for authenticated users'),
      '#description' => $this->t('If "yes", even though the user had opted to not allow cookies, this module will allow cookies while the user is logged in'),
      '#options' => [
        1 => $this->t('Yes'),
        0 => $this->t('No'),
      ],
      '#default_value' => in_array($config->get('force_allow_cookies_auth_users'), [0, 1]) ? $config->get('force_allow_cookies_auth_users') : 1,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('block_cookies.settings');
    $config->set('force_allow_cookies_auth_users', $form_state->getValue('force_allow_cookies_auth_users'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
