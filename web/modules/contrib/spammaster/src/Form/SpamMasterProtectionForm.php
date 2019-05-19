<?php

namespace Drupal\spammaster\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class controller.
 */
class SpamMasterProtectionForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spammaster_settings_protection_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    // Default settings.
    $config = $this->config('spammaster.settings_protection');

    $form['protection_header'] = [
      '#type' => 'vertical_tabs',
      '#title' => t('<h3>Protection Tools</h3>'),
      '#attached' => [
        'library' => [
          'spammaster/spammaster-styles',
        ],
      ],
    ];

    $form['message'] = [
      '#type' => 'details',
      '#title' => t('Block Message'),
      '#group' => 'protection_header',
    ];

    // Insert license key field.
    $form['message']['block_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Change block message:'),
      '#default_value' => $config->get('spammaster.block_message'),
      '#description' => $this->t('Message to display to blocked spam users who are not allowed to register, contact, or comment in your Drupal site. Keep it short.'),
      '#attributes' => [
        'class' => [
          'spammaster-responsive-49',
        ],
      ],
    ];

    // Insert basic tools table inside tree.
    $form['basic'] = [
      '#type' => 'details',
      '#title' => t('Basic Tools'),
      '#group' => 'protection_header',
      '#attributes' => [
        'class' => [
          'spammaster-responsive-25',
        ],
      ],
    ];

    $form['basic']['table_1'] = [
      '#type' => 'table',
      '#header' => [
          ['data' => 'Activate individual Basic Tools to implement Spam Master across your site.', 'colspan' => 4],
      ],
    ];
    $form['basic']['table_1']['addrow']['basic_firewall'] = [
      '#type' => 'select',
      '#title' => t('Firewall Scan'),
      '#options' => [
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.basic_firewall'),
      '#description' => t('Set this to <em>Yes</em> if you would like the Firewall scan implemented across your site. Greatly reduces server resources like CPU and Memory.'),
    ];
    $form['basic']['table_1']['addrow']['basic_registration'] = [
      '#type' => 'select',
      '#title' => t('Registration Scan'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.basic_registration'),
      '#description' => t('Set this to <em>Yes</em> if you would like the Registraion Scan for new registration attempts. Applies to registration form.'),
    ];
    $form['basic']['table_1']['addrow']['basic_comment'] = [
      '#type' => 'select',
      '#title' => t('Comment Scan'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.basic_comment'),
      '#description' => t('Set this to <em>Yes</em> if you would like the Comment Scan for new comment attempts. Applies to comment form.'),
    ];
    $form['basic']['table_1']['addrow']['basic_contact'] = [
      '#type' => 'select',
      '#title' => t('Contact Scan'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.basic_contact'),
      '#description' => t('Set this to <em>Yes</em> if you would like the Contact Scan to be display on the contact form.'),
    ];

    // Insert Extra tools table inside tree.
    $form['extra'] = [
      '#type' => 'details',
      '#title' => t('Extra Tools'),
      '#group' => 'protection_header',
      '#attributes' => [
        'class' => [
          'spammaster-responsive-25',
        ],
      ],
    ];

    $form['extra']['table_2'] = [
      '#type' => 'table',
      '#header' => [
          ['data' => 'Activate individual Extra Tools to implement Spam Master across your site.', 'colspan' => 4],
      ],
    ];
    $form['extra']['table_2']['addrow']['extra_honeypot'] = [
      '#type' => 'select',
      '#title' => t('Honeypot'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_honeypot'),
      '#description' => t('Set this to <em>Yes</em> if you would like two Honeypot fields implemented across your site forms.'),
    ];
    $form['extra']['table_2']['addrow']['extra_recaptcha'] = [
      '#type' => 'select',
      '#title' => t('Google re-Captcha V2'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_recaptcha'),
      '#description' => t('Set this to <em>Yes</em> if you would like Google re-Captcha V2 implemented across your site forms.'),
    ];
    // Insert addrow re-captcha api key.
    $form['extra']['table_2']['addrow']['extra_recaptcha_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google re-Captcha API Site Key:'),
      '#default_value' => $config->get('spammaster.extra_recaptcha_api_key'),
      '#description' => $this->t('Insert your Google re-Captcha api key.'),
      '#attributes' => [
        'class' => [
          'spammaster-responsive',
        ],
      ],
    ];
    // Insert addrow re-captcha secrete key.
    $form['extra']['table_2']['addrow']['extra_recaptcha_api_secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google re-Captcha API Secret Key:'),
      '#default_value' => $config->get('spammaster.extra_recaptcha_api_secret_key'),
      '#description' => $this->t('Insert your Google re-Captcha api secret key.'),
      '#attributes' => [
        'class' => [
          'spammaster-responsive',
        ],
      ],
    ];

    $form['extra']['table_3'] = [
      '#type' => 'table',
      '#header' => [],
    ];
    $form['extra']['table_3']['addrow']['extra_recaptcha_login'] = [
      '#type' => 'select',
      '#title' => t('re-Captcha on Login Form'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_recaptcha_login'),
      '#description' => t('Set this to <em>Yes</em> if you would like Google re-Captcha implemented on the Login Form.'),
    ];
    $form['extra']['table_3']['addrow']['extra_recaptcha_registration'] = [
      '#type' => 'select',
      '#title' => t('re-Captcha on Registration Form'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_recaptcha_registration'),
      '#description' => t('Set this to <em>Yes</em> if you would like Google re-Captcha implemented on the Registration Form.'),
    ];
    $form['extra']['table_3']['addrow']['extra_recaptcha_comment'] = [
      '#type' => 'select',
      '#title' => t('re-Captcha on Comment Form'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_recaptcha_comment'),
      '#description' => t('Set this to <em>Yes</em> if you would like Google re-Captcha implemented on the Comment Form.'),
    ];
    $form['extra']['table_3']['addrow']['extra_recaptcha_contact'] = [
      '#type' => 'select',
      '#title' => t('re-Captcha on Contact Form'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_recaptcha_contact'),
      '#description' => t('Set this to <em>Yes</em> if you would like Google re-Captcha implemented on the Contact Form.'),
    ];

    $form['extra']['table_3']['addrow1']['extra_honeypot_login'] = [
      '#type' => 'select',
      '#title' => t('Honeypot on Login Form'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_honeypot_login'),
      '#description' => t('Set this to <em>Yes</em> if you would like Honeypot on the Login Form.'),
    ];
    $form['extra']['table_3']['addrow1']['extra_honeypot_registration'] = [
      '#type' => 'select',
      '#title' => t('Honeypot on Registration Form'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_honeypot_registration'),
      '#description' => t('Set this to <em>Yes</em> if you would like Honeypot on the Registration Form.'),
    ];
    $form['extra']['table_3']['addrow1']['extra_honeypot_comment'] = [
      '#type' => 'select',
      '#title' => t('Honeypot on Comment Form'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_honeypot_comment'),
      '#description' => t('Set this to <em>Yes</em> if you would like Honeypot on the Comment Form.'),
    ];
    $form['extra']['table_3']['addrow1']['extra_honeypot_contact'] = [
      '#type' => 'select',
      '#title' => t('Honeypot on Contact Form'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.extra_honeypot_contact'),
      '#description' => t('Set this to <em>Yes</em> if you would like Honeypot on the Contact Form.'),
    ];

    // Insert Signatures tools table inside tree.
    $form['signature'] = [
      '#type' => 'details',
      '#title' => t('Signatures'),
      '#group' => 'protection_header',
      '#attributes' => [
        'class' => [
          'spammaster-responsive-25',
        ],
      ],
    ];
    $form['signature']['table_4'] = [
      '#type' => 'table',
      '#header' => [
          ['data' => 'Signatures are a huge deterrent against all forms of human spam.', 'colspan' => 4],
      ],
    ];
    $form['signature']['table_4']['addrow']['signature_registration'] = [
      '#type' => 'select',
      '#title' => t('Registration Signature'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.signature_registration'),
      '#description' => t('Set this to <em>Yes</em> if you would like a Protection Signature to be displayed on the registration form.'),
    ];
    $form['signature']['table_4']['addrow']['signature_login'] = [
      '#type' => 'select',
      '#title' => t('Login Signature'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.signature_login'),
      '#description' => t('Set this to <em>Yes</em> if you would like a Protection Signature to be displayed on the login form.'),
    ];
    $form['signature']['table_4']['addrow']['signature_comment'] = [
      '#type' => 'select',
      '#title' => t('Comment Signature'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.signature_comment'),
      '#description' => t('Set this to <em>Yes</em> if you would like a Protection Signature to be displayed on the comment form.'),
    ];
    $form['signature']['table_4']['addrow']['signature_contact'] = [
      '#type' => 'select',
      '#title' => t('Contact Signature'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.signature_contact'),
      '#description' => t('Set this to <em>Yes</em> if you would like a Protection Signature to be displayed on the contact form.'),
    ];

    // Insert email tools table inside tree.
    $form['email'] = [
      '#type' => 'details',
      '#title' => t('Emails & Reports'),
      '#group' => 'protection_header',
      '#attributes' => [
        'class' => [
          'spammaster-responsive-25',
        ],
      ],
    ];
    $form['email']['table_5'] = [
      '#type' => 'table',
      '#header' => [
        ['data' => 'An extra watchful eye over your drupal website security. Emails and reports are sent to the email address found in your drupal Configuration, Basic Site Settings.', 'colspan' => 4],
      ],
    ];
    $form['email']['table_5']['addrow']['email_alert_3'] = [
      '#type' => 'select',
      '#title' => t('Alert 3 Warning Email'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.email_alert_3'),
      '#description' => t('Set this to <em>Yes</em> to receive the alert 3 email. Only sent if your website has reached or is at a dangerous level.'),
    ];
    $form['email']['table_5']['addrow']['email_daily_report'] = [
      '#type' => 'select',
      '#title' => t('Daily Report Email'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.email_daily_report'),
      '#description' => t('Set this to <em>Yes</em> to receive the daily report for normal alert levels and spam probability percentage.'),
    ];
    $form['email']['table_5']['addrow']['email_weekly_report'] = [
      '#type' => 'select',
      '#title' => t('Weekly Report Email'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.email_weekly_report'),
      '#description' => t('Set this to <em>Yes</em> to receive the Weekly detailed email report.'),
    ];
    $form['email']['table_5']['addrow']['email_improve'] = [
      '#type' => 'select',
      '#title' => t('Help us improve Spam Master'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('spammaster.email_improve'),
      '#description' => t('Set this to <em>Yes</em> to help us improve Spam Master with weekly statistical data, same as your weekly report.'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('spammaster.settings_protection');
    $config->set('spammaster.block_message', $form_state->getValue('block_message'));
    $config->set('spammaster.basic_registration', $form_state->getValue('table_1')['addrow']['basic_registration']);
    $config->set('spammaster.basic_comment', $form_state->getValue('table_1')['addrow']['basic_comment']);
    $config->set('spammaster.basic_contact', $form_state->getValue('table_1')['addrow']['basic_contact']);
    $config->set('spammaster.extra_honeypot', $form_state->getValue('table_2')['addrow']['extra_honeypot']);
    $config->set('spammaster.extra_recaptcha', $form_state->getValue('table_2')['addrow']['extra_recaptcha']);
    $config->set('spammaster.extra_recaptcha_api_key', $form_state->getValue('table_2')['addrow']['extra_recaptcha_api_key']);
    $config->set('spammaster.extra_recaptcha_api_secret_key', $form_state->getValue('table_2')['addrow']['extra_recaptcha_api_secret_key']);
    $config->set('spammaster.extra_recaptcha_login', $form_state->getValue('table_3')['addrow']['extra_recaptcha_login']);
    $config->set('spammaster.extra_recaptcha_registration', $form_state->getValue('table_3')['addrow']['extra_recaptcha_registration']);
    $config->set('spammaster.extra_recaptcha_comment', $form_state->getValue('table_3')['addrow']['extra_recaptcha_comment']);
    $config->set('spammaster.extra_recaptcha_contact', $form_state->getValue('table_3')['addrow']['extra_recaptcha_contact']);
    $config->set('spammaster.extra_honeypot_login', $form_state->getValue('table_3')['addrow1']['extra_honeypot_login']);
    $config->set('spammaster.extra_honeypot_registration', $form_state->getValue('table_3')['addrow1']['extra_honeypot_registration']);
    $config->set('spammaster.extra_honeypot_comment', $form_state->getValue('table_3')['addrow1']['extra_honeypot_comment']);
    $config->set('spammaster.extra_honeypot_contact', $form_state->getValue('table_3')['addrow1']['extra_honeypot_contact']);
    $config->set('spammaster.signature_registration', $form_state->getValue('table_4')['addrow']['signature_registration']);
    $config->set('spammaster.signature_login', $form_state->getValue('table_4')['addrow']['signature_login']);
    $config->set('spammaster.signature_comment', $form_state->getValue('table_4')['addrow']['signature_comment']);
    $config->set('spammaster.signature_contact', $form_state->getValue('table_4')['addrow']['signature_contact']);
    $config->set('spammaster.email_alert_3', $form_state->getValue('table_5')['addrow']['email_alert_3']);
    $config->set('spammaster.email_daily_report', $form_state->getValue('table_5')['addrow']['email_daily_report']);
    $config->set('spammaster.email_weekly_report', $form_state->getValue('table_5')['addrow']['email_weekly_report']);
    $config->set('spammaster.email_improve', $form_state->getValue('table_5')['addrow']['email_improve']);
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'spammaster.settings_protection',
    ];
  }

}
