<?php

namespace Drupal\just_smtp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\encrypt\Entity\EncryptionProfile;

/**
 * Implements the Just SMTP admin settings form.
 */
class JustSMTPConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'just_smtp_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['just_smtp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('just_smtp.settings');

    // Check if PHPMailer is installed (either via Composer or manually).
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')
    && !file_exists(DRUPAL_ROOT . '/libraries/PHPMailer/src/PHPMailer.php')
    ) {
      drupal_set_message(t('The PHPMailer library has not yet been installed.'), 'error');
    }

    $form['onoff'] = array(
      '#type'  => 'fieldset',
      '#title' => t('Install options'),
    );
    $form['onoff']['just_smtp_on'] = array(
      '#type'          => 'radios',
      '#title'         => t('Turn this module on or off'),
      '#default_value' => ($config->get('just_smtp_on') ? $config->get('just_smtp_on') : 0),
      '#options'       => array(1 => t('On'), 0 => t('Off')),
      '#description'   => t('When the module is disabled, it will use the default system mail interface.'),
    );

    $form['server'] = array(
      '#type'  => 'fieldset',
      '#title' => t('SMTP server settings'),
    );
    $form['server']['just_smtp_host'] = array(
      '#type'          => 'textfield',
      '#title'         => t('SMTP server'),
      '#default_value' => ($config->get('just_smtp_host') ? $config->get('just_smtp_host') : 'localhost'),
      '#description'   => t('The address of your outgoing SMTP server.'),
    );
    $url = Url::fromUri('http://gmail.google.com/support/bin/answer.py?answer=13287');
    $form['server']['just_smtp_port'] = array(
      '#type'          => 'textfield',
      '#title'         => t('SMTP port'),
      '#size'          => 6,
      '#maxlength'     => 6,
      '#default_value' => ($config->get('just_smtp_port') ? $config->get('just_smtp_port') : 25),
      '#description'   => t('The default SMTP port is 25. Use 465 for an SSL connection. Gmail uses port 465. See @url for more information on configuring for use with Gmail. For secure connections (SSL or STARTTLS) the certificate presented by the server must be valid for PHP 5.6 or higher.', array('@url' => Link::fromTextAndUrl(t('this page'), $url)->toString())),
    );

    // Only display the option if openssl is installed.
    if (function_exists('openssl_open')) {
      $encryption_options = array(
        'auto' => t('Auto'),
        'ssl'      => t('Force SSL'),
      );
      $encryption_description = t('Encryption is enabled if the server supports it. Use force if auto-detection fails.');
    }
    // If openssl is not installed, use normal protocol.
    else {
      $encryption_options = array('auto' => t('Auto'));
      $encryption_description = t('Your PHP installation does not have SSL enabled. See the @url page on php.net for more information. Gmail requires SSL.', array('@url' => l(t('OpenSSL Functions'), 'http://php.net/openssl')));
    }
    $form['server']['just_smtp_protocol'] = array(
      '#type'          => 'select',
      '#title'         => t('Encryption'),
      '#default_value' => ($config->get('just_smtp_protocol') ? $config->get('just_smtp_protocol') : 'auto'),
      '#options'       => $encryption_options,
      '#description'   => $encryption_description,
    );

    $form['auth'] = array(
      '#type'        => 'fieldset',
      '#title'       => t('SMTP Authentication'),
      '#description' => t('Leave blank if your SMTP server does not require authentication.'),
    );
    $form['auth']['just_smtp_username'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Username'),
      '#default_value' => ($config->get('just_smtp_username') ? $config->get('just_smtp_username') : ''),
      '#description'   => t('SMTP Username.'),
    );
    $form['auth']['just_smtp_password'] = array(
      '#type'          => 'password',
      '#title'         => t('Password'),
      '#default_value' => ($config->get('just_smtp_password') ? $config->get('just_smtp_password') : ''),
      '#description'   => t('SMTP password. If you have already entered your password before, you should leave this field blank, unless you want to change the stored password.'),
    );

    if (\Drupal::moduleHandler()->moduleExists('encrypt')) {
      $form['auth']['just_smtp_encrypt'] = array(
        '#type'          => 'checkbox',
        '#title'         => t('Encrypt'),
        '#default_value' => ($config->get('just_smtp_encrypt') ? $config->get('just_smtp_encrypt') : '0'),
        '#description'   => t('Encrypt the password with the <em>Encrypt</em> module.'),
      );

      $profile_manager = \Drupal::service('encrypt.encryption_profile.manager');
      $profiles = ['' => 'Select a profile'];
      $profiles = array_merge($profiles, $profile_manager->getEncryptionProfileNamesAsOptions());
      $profile_default = ($config->get('just_smtp_encrypt_profile') ? $config->get('just_smtp_encrypt_profile') : '');

      $form['auth']['just_smtp_encrypt_profile'] = array(
        '#type'          => 'select',
        '#title'         => t('Encryption Profile'),
        '#options'       => $profiles,
        '#default_value' => $profile_default,
        '#description'   => 'If no profiles exist, create an <a href="/admin/config/system/encryption/profiles">Encryption profile</a>.',
        '#states' => array(
          'visible' => array(
            ':input[name="just_smtp_encrypt"]' => array('checked' => TRUE),
          ),
        ),
      );

      if (($config->get('just_smtp_encrypt') ? $config->get('just_smtp_encrypt') : 0)
        && !empty($profile_default)
      ) {
        $encryption_profile = EncryptionProfile::load($profile_default);
        $encrypted_password = ($config->get('just_smtp_password') ? $config->get('just_smtp_password') : '');
        $password = \Drupal::service('encryption')->encrypt($encrypted_password, $encryption_profile);

        $form['auth']['just_smtp_password']['#default_value'] = $password;
      }

    }

    $form['email_test'] = array(
      '#type'  => 'fieldset',
      '#title' => t('Send test e-mail'),
    );
    $form['email_test']['just_smtp_test_address'] = array(
      '#type'          => 'textfield',
      '#title'         => t('E-mail address to send a test e-mail to'),
      '#default_value' => '',
      '#description'   => t('Type in an address to have a test e-mail sent there.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if ($form_state->getValue('just_smtp_on')) {

      if (empty($form_state->getValue('just_smtp_host'))) {
        $form_state->setError($form['server']['just_smtp_host'], $this->t('You must enter an SMTP server address.'));
      }

      if (empty($form_state->getValue('just_smtp_port'))) {
        $form_state->setError($form['server']['just_smtp_port'], $this->t('You must enter an SMTP port number.'));
      }
    }

    if (!$form_state->getValue('just_smtp_password') && !empty($form_state->getValue('just_smtp_username'))) {
      // SMTP password must be re-entered if encryption is being enabled.
      if ($form_state->getValue('just_smtp_encrypt')
        && $form_state->getValue('just_smtp_encrypt') != $form['auth']['just_smtp_encrypt']['#default_value']
        ) {
        $form_state->setError($form['auth']['just_smtp_encrypt'], $this->t('The password can only be encrypted if it is re-entered.'));
      }
      // SMTP password must be re-entered if encyrption is being disabled.
      if (!$form_state->getValue('just_smtp_encrypt')
        && $form_state->getValue('just_smtp_encrypt') != $form['auth']['just_smtp_encrypt']['#default_value']
        ) {
        $form_state->setError($form['auth']['just_smtp_encrypt'], $this->t('The password cannot be decrypted. It must be re-entered.'));
      }
      // SMTP password must be re-entered if changing encryption profiles.
      if ($form_state->getValue('just_smtp_encrypt')
        && $form_state->getValue('just_smtp_encrypt_profile') != $form['auth']['just_smtp_encrypt_profile']['#default_value']
        ) {
        $form_state->setError($form['auth']['just_smtp_password'], $this->t('When changing Encryption profiles, the password must be re-entered.'));
      }
    }
    // Conditional behavior for Encrypt Profile field.
    if ($form_state->getValue('just_smtp_encrypt')
      && empty($form_state->getValue('just_smtp_encrypt_profile'))
      ) {
      $form_state->setError($form['auth']['just_smtp_encrypt_profile'], $this->t('An Encryption profile must be selected.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('just_smtp.settings')
      ->set('just_smtp_on', $form_state->getValue('just_smtp_on'))
      ->set('just_smtp_host', $form_state->getValue('just_smtp_host'))
      ->set('just_smtp_port', $form_state->getValue('just_smtp_port'))
      ->set('just_smtp_protocol', $form_state->getValue('just_smtp_protocol'))
      ->set('just_smtp_username', $form_state->getValue('just_smtp_username'))
      ->set('just_smtp_encrypt', $form_state->getValue('just_smtp_encrypt'))
      ->set('just_smtp_encrypt_profile', $form_state->getValue('just_smtp_encrypt_profile'));

    // If username is empty, then set password as empty, too.
    if (empty($form_state->getValue('just_smtp_username'))) {
      $password = '';
    }
    elseif ($form_state->getValue('just_smtp_password')) {
      $password = $form_state->getValue('just_smtp_password');
    }
    // If password field is empty, then set to null.
    else {
      $password = NULL;
    }

    // If encryption is enabled, then encrypt the smtp password.
    if ($form_state->getValue('just_smtp_encrypt') && $form_state->getValue('just_smtp_password')) {
      $encryption_profile = EncryptionProfile::load($form_state->getValue('just_smtp_encrypt_profile'));
      $password = \Drupal::service('encryption')->encrypt($form_state->getValue('just_smtp_password'), $encryption_profile);
    }

    // If user entered a password, then save it.
    if (!is_null($password)) {
      $this->config('just_smtp.settings')
        ->set('just_smtp_password', $password);
    }

    $current_mailsystem = $this->config('system.mail')->get('interface.default');

    // If enabled, set Just SMTP as default mail system.
    if ($form_state->getValue('just_smtp_on') == '1'
      && $current_mailsystem != 'JustSMTPMailSystem'
    ) {

      $this->config('just_smtp.settings')
        ->set('just_smtp_prior_mailsystem', $current_mailsystem);
      \Drupal::configFactory()->getEditable('system.mail')->set('interface.default', 'JustSMTPMailSystem')->save();
    }
    // If disabled, set prior mail system as the default mail system.
    elseif ($form_state->getValue('just_smtp_on') == '0'
    && $current_mailsystem == 'JustSMTPMailSystem'
    ) {

      $prior_mailsystem = $this->config('just_smtp.settings')->get('just_smtp_prior_mailsystem');
      \Drupal::configFactory()->getEditable('system.mail')->set('interface.default', $prior_mailsystem)->save();
    }

    $this->config('just_smtp.settings')->save();

    // If an address was given, send a test e-mail message.
    $test_address = $form_state->getValue('just_smtp_test_address');
    if ($test_address != '') {
      // Clear the variable so only one message is sent.
      global $language;
      $params['subject'] = 'Drupal SMTP test e-mail';
      $params['body']    = 'If you receive this message it means your site is capable of using SMTP to send e-mail.';

      $newMail = \Drupal::service('plugin.manager.mail');
      $newMail->mail('just_smtp', 'just-smtp-test', $test_address, $language, $params);

      $url = Url::fromRoute('dblog.overview');
      $message = [
        '@email' => $test_address,
        '@check' => Link::fromTextAndUrl(t('check the logs'), $url)->toString(),
      ];
      drupal_set_message(t('A test e-mail has been sent to @email. You may want to @check for any error messages.', $message));
    }

    parent::submitForm($form, $form_state);
  }

}
