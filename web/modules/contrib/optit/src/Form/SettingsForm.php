<?php

namespace Drupal\optit\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a form that configures optit settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'optit_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'optit.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('optit.settings');

    $form['credentials'] = array(
      '#type' => 'fieldset',
      '#title' => t('Credentials'),
    );
    $form['credentials']['username'] = array(
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#default_value' => $config->get('username'),
      '#required' => TRUE,
    );

    // @todo: Secure password field and storage! Preferably move the entire setting to settings.php file!
    // @todo: See https://www.drupal.org/node/1834604 discussion.
    $form['credentials']['password'] = array(
      '#type' => 'textfield',
      '#title' => t('Password'),
      '#default_value' =>  $config->get('password'),
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('optit.settings')
      ->set('username', $values['username'])
      ->set('password', $values['password'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
