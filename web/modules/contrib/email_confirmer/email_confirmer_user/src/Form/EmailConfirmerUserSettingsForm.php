<?php

namespace Drupal\email_confirmer_user\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Email confirmer user settings form.
 */
class EmailConfirmerUserSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_confirmer_user_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'email_confirmer_user.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\Core\Config\ImmutableConfig $config */
    $config = $this->config('email_confirmer_user.settings');

    // User email address confirmation on change options.
    $subconfig = $config->get('user_email_change');
    $form['email_change'] = [
      '#type' => 'details',
      '#title' => $this->t('On user email change'),
      '#open' => TRUE,
    ];

    $form['email_change']['user_email_change_confirm'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require confirmation'),
      '#description' => $this->t('This enables or disables the email address confirmation when a user updates their email address.'),
      '#default_value' => $subconfig['enabled'],
    ];

    $form['email_change']['user_email_change_notify_current'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send notification to the current email address'),
      '#description' => $this->t('An informational message will be send to the original email address when the user attempts to change their address.'),
      '#default_value' => $subconfig['notify_current'],
      '#states' => [
        'disabled' => [
          ':input[name="user_email_change_confirm"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['email_change']['user_email_change_consider_existent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Consider existent confirmations'),
      '#description' => $this->t('No confirmation will be required for previously confirmed email addresses by the same user.'),
      '#default_value' => $subconfig['consider_existent'],
      '#states' => [
        'disabled' => [
          ':input[name="user_email_change_confirm"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['email_change']['user_email_change_limit_realm'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Consider only user related confirmations'),
      '#description' => $this->t('Leave unchecked to consider the existing confirmations launched by the same user in other situations in which the email confirmer was used.'),
      '#default_value' => $subconfig['limit_user_realm'],
      '#states' => [
        'disabled' => [
          [
            ':input[name="user_email_change_confirm"]' => ['checked' => FALSE],
          ],
          [
            ':input[name="user_email_change_consider_existent"]' => ['checked' => FALSE],
          ],
        ],
      ],
    ];

    // User log in options.
    $subconfig = $config->get('user_login');
    $form['user_login'] = [
      '#type' => 'details',
      '#title' => $this->t('On user log in'),
      '#open' => TRUE,
    ];

    $form['user_login']['sync_core_onfirstlogin_confirmation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Record email confirmation on first user log in'),
      '#description' => $this->t('When a new user logs in for the first time, a confirmation will be recorded by the email confirmer to work with it onwards.'),
      '#default_value' => $subconfig['sync_core_confirmation'],
    ];

    $form['user_login']['sync_core_onetimeloginlinks'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Record email confirmation when user logs in through a one-time log in link'),
      '#description' => $this->t('When a user logs in by opening a one-time log in link, as in the password reset, a confirmation will be recorded by the email confirmer to work with it onwards.'),
      '#default_value' => $subconfig['sync_core_onetimeloginlinks'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\ImmutableConfig $config */
    $config = $this->config('email_confirmer_user.settings');

    $config->set('user_email_change', [
      'enabled' => $form_state->getValue('user_email_change_confirm') && TRUE,
      'notify_current' => $form_state->getValue('user_email_change_notify_current') && TRUE,
      'consider_existent' => $form_state->getValue('user_email_change_consider_existent') && TRUE,
      'limit_user_realm' => $form_state->getValue('user_email_change_limit_realm') && TRUE,
    ])
      ->set('user_login', [
        'sync_core_confirmation' => $form_state->getValue('sync_core_onfirstlogin_confirmation') && TRUE,
        'sync_core_onetimeloginlinks' => $form_state->getValue('sync_core_onetimeloginlinks') && TRUE,
      ])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
