<?php

/**
 * @file
 * Contains \Drupal\logouttab\Form\LogouttabSettingsForm.
 */

namespace Drupal\logouttab\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure logouttab settings for this site.
 */
class LogouttabSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'logouttab_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['logouttab.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('logouttab.settings');

    $form['url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('URL for the account logout page'),
      '#description' => $this->t('Enter the relative path for the user account logout page.'),
      '#default_value' => $config->get('url'),
      '#field_prefix' => $this->url('<none>', [], ['absolute' => TRUE]),
    );
    $form['weight'] = array(
      '#type' => 'weight',
      '#title' => $this->t('Weight of the logout tab'),
      '#default_value' => $config->get('weight'),
      '#delta' => 30,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('logouttab.settings')
      ->set('url', $form_state->getValue('url'))
      ->set('weight', $form_state->getValue('weight'))
      ->save();
  }

}
