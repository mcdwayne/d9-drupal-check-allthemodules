<?php

namespace Drupal\instapage\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\instapage\Api;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\instapage\Form
 */
class SettingsForm extends ConfigFormBase {

  private $api;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\instapage\Api $api
   */
  public function __construct(ConfigFactoryInterface $config_factory, Api $api) {
    $this->setConfigFactory($config_factory);
    $this->api = $api;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('instapage.api')
    );
  }

  /**
   * @return string
   */
  public function getFormId() {
    return 'instapage_admin_settings_form';
  }

  /**
   * @return array
   */
  protected function getEditableConfigNames() {
    return [
      'instapage.settings',
    ];
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('instapage.settings');
    $email = $config->get('instapage_user_id');
    $token = $config->get('instapage_user_token');

    // If the user is logged in.
    if ($email && $token) {

      // Get account keys from the API.
      $result = $this->api->getAccountKeys($token);
      if (isset($result['error']) && $result->error) {
        $form_state->setErrorByName('form', $this->t('Error from Instapage API: @instapage_msg', ['@instapage_msg' => $result->error_message]));

        // Clear variables on form error to match the initial state.
        $config->set('instapage_user_id', FALSE);
        $config->set('instapage_plugin_hash', FALSE);
        $config->save();
      }
      else {
        // If user is logged in, show info and Disconnect button.
        $form['info']['#markup'] = $this->t('You are logged in as @user.', ['@user' => $email]);
        $form['info']['#markup'] .= '<p>' . $this->t('Administer your pages <a href="@link">here</a>.', ['@link' => Url::fromRoute('instapage.landing_pages')->toString()]) . '</p>';

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = ['#type' => 'submit', '#value' => $this->t('Disconnect')];
        return $form;
      }
    }

    // The user is not logged in.
    $form['info']['#markup'] = $this->t('Type in email and password of your Instapage account.');
    $form['instapage_user_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
    ];

    $form['instapage_user_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = ['#type' => 'submit', '#value' => $this->t('Save configuration')];

    return $form;

  }

  /**
   * Form validation callback.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Check if the credentials are correct.
    if ($this->config('instapage.settings')->get('instapage_user_id') == FALSE) {
      $email = trim($form_state->getValue('instapage_user_email'));
      $password = trim($form_state->getValue('instapage_user_password'));
      $result = $this->api->authenticate($email, $password);
      if (isset($result['status']) && $result['status'] == 200) {
        // Login successful.
        $form_state->setValue('instapage_user_id', $email);
        // Override the password with the users token.
        $form_state->setValue('instapage_plugin_hash', $result['content']);
      }
      else {
        // Login failed.
        $form_state->setErrorByName('form', $this->t('Error from Instapage API: @instapage_msg', ['@instapage_msg' => $result['content']]));
      }
    }
  }

  /**
   * Form submit callback.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('instapage.settings');

    // If the user is not logged in save the credentials.
    if ($config->get('instapage_user_id') == FALSE) {
      $email = $form_state->getValue('instapage_user_id');
      $token = $form_state->getValue('instapage_plugin_hash');
      $this->api->registerUser($email, $token);
    }
    else {
      // Otherwise log out the user.
      $config->set('instapage_user_id', FALSE);
      $config->set('instapage_user_token', FALSE);
      $config->save();
    }

    parent::submitForm($form, $form_state);
  }

}
