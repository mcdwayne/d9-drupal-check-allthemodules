<?php

namespace Drupal\flexiform\Plugin\FormElement;

use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flexiform\FormElement\ContextAwareFormElementBase;

/**
 * Form element class for user accounts.
 *
 * @FormElement(
 *   id = "account_info",
 *   label = @Translation("Account Info"),
 *   context = {
 *     "account" = @ContextDefinition("entity:user", label = @Translation("User")),
 *   }
 * )
 */
class AccountInfo extends ContextAwareFormElementBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // @todo: Inject?
    $config = \Drupal::config('user.settings');
    $user = \Drupal::currentUser();
    $account = $this->getContext('account')->getContextValue();

    $form['#type'] = 'container';
    $form['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#description' => $this->t('A valid email address. All emails from the system will be sent to this address. The email address is not made public and will only be used if you wish to receive a new password or wish to receive certain news or notifications by email.'),
      '#required' => !(!$account->getEmail() && $user->hasPermission('administer users')),
      '#default_value' => (!$account->isAnonymous() ? $account->getEmail() : ''),
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#maxlength' => USERNAME_MAX_LENGTH,
      '#description' => $this->t("Several special characters are allowed, including space, period (.), hyphen (-), apostrophe ('), underscore (_), and the @ sign."),
      '#required' => TRUE,
      '#attributes' => [
        'class' => [
          'username',
        ],
        'autocorrect' => 'off',
        'autocapitalize' => 'off',
        'spellcheck' => 'false',
      ],
      '#default_value' => !$account->isAnonymous() ? $account->getAccountName() : '',
      '#access' => $account->isAnonymous() || $user->id() == $account->id() && $user->hasPermission('change own username') || $user->hasPermission('administer users'),
    ];

    // Display a passowrd field only for existing users or when a user is
    // allowed to assign a new password.
    if (!$account->isAnonymous()) {
      $form['pass'] = [
        '#type' => 'password_confirm',
        '#size' => 25,
        '#description' => $this->t('To changed the current user password, entity the new password in both fields.'),
      ];

      if ($user->id() == $account->id()) {
        $form['current_pass'] = [
          '#type' => 'password',
          '#title' => $this->t('Current password'),
          '#size' => 25,
          '#weight' => -5,
          '#attributes' => [
            'autocomplete' => 'off',
          ],
          '#description' => $this->t('Required if you want to change the %mail or %pass below. <a href=":request_new_url" title="Send password reset instructions via email.">Reset your password</a>.', [
            '%mail' => $form['mail']['#title'],
            '%pass' => $this->t('Password'),
            ':request_new_url' => Url::fromRoute('user.pass')->toString(),
          ]),
        ];
      }
    }
    elseif (!$config->get('verify_email') || $user->hasPermission('administer users')) {
      $form['pass'] = [
        '#type' => 'password_confirm',
        '#size' => 25,
        '#description' => $this->t('Provide a password for the new account in both fields.'),
        '#required' => TRUE,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntities(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->getContext('account')->getContextValue();
    $element_values = $form_state->getValue($form['#parents']);

    // Set the existing password of set in form state.
    $current_pass = trim($element_values['current_pass']);
    if (strlen($current_pass) > 0) {
      $account->setExistingPassword($current_pass);
    }

    $account->pass->value = $element_values['pass'];
    $account->name->value = $element_values['name'];
    $account->mail->value = $element_values['mail'];
  }

}
