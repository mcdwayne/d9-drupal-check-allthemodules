<?php

/**
 * @file
 * Contains \Drupal\mail_redirect\Form\MailRedirectAdminSettings.
 */

namespace Drupal\mail_redirect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class MailRedirectAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mail_redirect_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('mail_redirect.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mail_redirect.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // system settings     
    $form['mail_redirect_opt'] = [
      '#type' => 'radios',
      '#title' => t('Mail Redirect Method'),
      '#options' => [
        'none' => t('Deliver mail normally'),
        'domain' => t('Redirect each recipient to a catch-all domain'),
        'address' => t('Redirect each message to a single address'),
      ],
      '#default_value' => \Drupal::config('mail_redirect.settings')->get('mail_redirect_opt') ? 
        \Drupal::config('mail_redirect.settings')->get('mail_redirect_opt') : 'none',
    ];

    $form['mail_redirect_domain'] = [
      '#type' => 'textfield',
      '#title' => t('Redirect Mail Domain'),
      '#default_value' => \Drupal::config('mail_redirect.settings')->get('mail_redirect_domain'),
      '#description' => t("Set the redirect mail domain to that of your catch-all mail test server. See README.txt for more info."),
      '#states' => [
        // Show the setting based on the mail redirect method setting.
        'visible' => [
          ':input[name="mail_redirect_opt"]' => [
            'value' => 'domain'
            ]
          ]
        ],
    ];

    $form['mail_redirect_address'] = [
      '#type' => 'textfield',
      '#title' => t('Redirect Mail Address'),
      '#description' => t('Redirect all mail to this address for testing.'),
      '#default_value' => \Drupal::config('mail_redirect.settings')->get('mail_redirect_address'),
      '#states' => [
        // Show the setting based on the mail redirect method setting.
        'visible' => [
          ':input[name="mail_redirect_opt"]' => [
            'value' => 'address'
            ]
          ]
        ],
    ];

    // list of emails to not redirect to 
    // @todo replace with add more list of user's and possibly a role selector
    $form['mail_redirect_skip_redirect'] = [
      '#type' => 'textarea',
      '#title' => t('Skip Redirect'),
      '#default_value' => \Drupal::config('mail_redirect.settings')->get('mail_redirect_skip_redirect'),
      '#description' => t("Enter a CSV list of email addresses to ignore when doing mail redirect. In other words these email addresses will not be redirected and will
      receive email as usual."),
    ];

    $form['mail_redirect_nomail'] = [
      '#type' => 'checkbox',
      '#title' => t('Discard Redirects'),
      '#default_value' => \Drupal::config('mail_redirect.settings')->get('mail_redirect_nomail'),
      '#description' => t("Check this if you do not want the redirected mail to actually be emailed; simply discarded. NOTE: this has no impact on the emails 
      listed in the Skip Redirect section above."),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $vals = $form_state->getValues();

    // Validate the domain name.
    if ($vals['mail_redirect_opt'] == 'domain' && !empty($vals['mail_redirect_domain']) && !self::isValidDomain($vals['mail_redirect_domain'])) {
      $form_state->setErrorByName('mail_redirect_domain', t('%name is not a valid domain.', [
        '%name' => $vals['mail_redirect_domain']
        ]));
    }

    // Validate the email address.
    if ($vals['mail_redirect_opt'] == 'address' && !empty($vals['mail_redirect_address']) && !valid_email_address($vals['mail_redirect_address'])) {
      $form_state->setErrorByName('mail_redirect_address', t('%address is not a valid e-mail address.', [
        '%address' => $vals['mail_redirect_address']
        ]));
    }

    // Ensure a value is set for the option chosen.
    if ($vals['mail_redirect_opt'] == 'domain' && empty($vals['mail_redirect_domain'])) {
      $form_state->setErrorByName('mail_redirect_domain', t('A domain name is required in order to redirect each recipient to a catch-all domain.'));
    }
    elseif ($vals['mail_redirect_opt'] == 'address' && empty($vals['mail_redirect_address'])) {
      $form_state->setErrorByName('mail_redirect_address', t('A valid e-mail address is required in order to redirect each message to a catch-all address.'));
    }
  }
  
  // D8 removed drupal_valid_http_host() so we write our own
  public static function isValidDomain($domain) {
    return (
      //valid chars check
      preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain) 
      //overall length check
      && preg_match("/^.{1,253}$/", $domain)
      //length of each label 
      && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain)); 
    }
}
