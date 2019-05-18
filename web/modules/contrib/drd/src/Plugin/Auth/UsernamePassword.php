<?php

namespace Drupal\drd\Plugin\Auth;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Plugin implementation of the 'username_password' DRD authentication.
 *
 * @Auth(
 *   id = "username_password",
 *   label = @Translation("Username and Password")
 * )
 */
class UsernamePassword extends Base {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function storeSettingRemotely() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array &$form, array $condition) {
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => '',
      '#states' => [
        'required' => $condition,
      ],
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#default_value' => '',
      '#states' => [
        'required' => $condition,
      ],
    ];
    $form['description'] = [
      '#markup' => $this->t('You can authenticate with a real user being available on the remote domains. In this case you have to provide the username and password of that user. Please note, that remote actions will then be executed with the permissions of that user, so it should be one with an admin role.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormValues(FormStateInterface $form_state) {
    /* @var \Drupal\drd\Encryption $service */
    $service = \Drupal::service('drd.encrypt');
    $settings = [
      'username' => $form_state->getValue('username'),
      'password' => $form_state->getValue('password'),
    ];
    $service->encrypt($settings);
    return $settings;
  }

}
