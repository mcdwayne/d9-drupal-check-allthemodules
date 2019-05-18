<?php

/**
 * @file
 * Contains \Drupal\securesite\Form\SecuresiteSettingsForm.
 */

namespace Drupal\securesite\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Form\FormStateInterface;
use  Drupal\Core\DrupalKernel;

class SecuresiteSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'securesite_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('securesite.settings');
    $anonymous_user = new AnonymousUserSession();
    $form['authentication'] = array(
      '#type' => 'fieldset',
      '#title' => t('Authentication'),
      '#description' => t('Enable Secure Site below. Users must have the <em>!access</em> permission in order to access the site if authentication is forced.', array('!access' => l(t('access secured pages'), 'admin/people/permissions', array('fragment' => 'module-securesite'))))
    );
    $form['authentication']['securesite_enabled'] = array(
      '#type' => 'radios',
      '#title' => t('Force authentication'),
      '#default_value' => $config->get('securesite_enabled'),
      '#options' => array(
        SECURESITE_DISABLED => t('Never'),
        SECURESITE_ALWAYS => t('Always'),
        SECURESITE_OFFLINE => t('During maintenance'),
      ),
      '#description' => t('Choose when to force authentication.'),
    );
    $form['authentication']['securesite_type'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Allowed authentication types'),
      '#default_value' => $config->get('securesite_type'),
      '#options' => array(
        SECURESITE_DIGEST => t('HTTP digest'),
        SECURESITE_BASIC => t('HTTP basic'),
        SECURESITE_FORM => t('HTML log-in form'),
      ),
      '#required' => TRUE,
    );
    $form['authentication']['securesite_type']['#description'] = "\n<p>" .
      t('HTTP authentication requires extra configuration if PHP is not installed as an Apache module. See the !link section of the Secure Site help for details.', array('!link' => l(t('Known issues'), 'admin/help/securesite', array('fragment' => 'issues')))) . "</p>\n<p>" .
      t('Digest authentication protects a user&rsquo;s password from eavesdroppers when you are not using SSL to encrypt the connection. However, it can only be used when a copy of the password is stored on the server.') . ' ' .
      t('For security reasons, Drupal does not store passwords. You will need to configure scripts to securely save passwords and authenticate users. See the !link section of the Secure Site help for details.', array('!link' => l(t('Secure password storage'), 'admin/help/securesite', array('fragment' => 'passwords')))) . "</p>\n<p>" .
      t('When digest authentication is enabled, passwords will be saved when users log in or set their passwords. If you use digest authentication to protect your whole site, you should allow guest access or allow another authentication type until users whose passwords are not yet saved have logged in. Otherwise, <strong>you may lock yourself out of your own site.</strong>') . '</p>' . "\n";
    $form['authentication']['securesite_digest_script'] = array(
      '#type' => 'textarea',
      '#title' => t('Digest authentication script'),
      '#default_value' => $config->get('securesite_digest_script'),
      '#description' => t('Enter the digest authentication script exactly as it should appear on the command line. Use absolute paths.'),
      '#rows' => 2,
    );
    $form['authentication']['securesite_password_script'] = array(
      '#type' => 'textarea',
      '#title' => t('Password storage script'),
      '#default_value' => $config->get('securesite_password_script'),
      '#description' => t('Enter the password storage script exactly as it should appear on the command line. Use absolute paths.'),
      '#rows' => 2,
    );
    $form['authentication']['securesite_realm'] = array(
      '#type' => 'textfield',
      '#title' => t('Authentication realm'),
      '#default_value' => $config->get('securesite_realm'),
      '#length' => 30,
      '#maxlength' => 40,
      '#description' => t('Name to identify the log-in area in the HTTP authentication dialog.'),
    );
    $form['guest'] = array(
      '#type' => 'fieldset',
      '#title' => t('Guest access'),
      '#description' => t('Guest access allows anonymous users to view secure pages, though they will still be prompted for a user name and password. If you give anonymous users the <em>!access</em> permission, you can set the user name and password for anonymous users below.', array('!access' => l(t('access secured pages'), 'admin/people/permissions', array('fragment' => 'module-securesite')))),
    );
    $guest_access = !$anonymous_user->hasPermission('access secured pages');
    $form['guest']['securesite_guest_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Guest user'),
      '#default_value' => $config->get('securesite_guest_name'),
      '#length' => 30,
      '#maxlength' => 40,
      '#description' => t('Do not use the name of a registered user. Leave empty to accept any name.'),
      '#disabled' => $guest_access,
    );
    $form['guest']['securesite_guest_pass'] = array(
      '#type' => 'textfield',
      '#title' => t('Guest password'),
      '#default_value' => $config->get('securesite_guest_pass'),
      '#length' => 30,
      '#maxlength' => 40,
      '#description' => t('Leave empty to accept any password.'),
      '#disabled' => $guest_access,
    );
    $form['login_form'] = array(
      '#type' => 'fieldset',
      '#title' => t('Customize HTML forms'),
      '#description' => t('Configure the message displayed on the HTML log-in form (if enabled) and password reset form below.')
    );
    $form['login_form']['securesite_login_form'] = array(
      '#type' => 'textarea',
      '#title' => t('Custom message for HTML log-in form'),
      '#default_value' => $config->get('securesite_login_form'),
      '#length' => 60,
      '#height' => 3,
    );
    $form['login_form']['securesite_reset_form'] = array(
      '#type' => 'textarea',
      '#title' => t('Custom message for password reset form'),
      '#default_value' => $config->get('securesite_reset_form'),
      '#length' => 60,
      '#height' => 3,
      '#description' => t('Leave empty to disable Secure Site&rsquo;s password reset form.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state){
    foreach ($form_state['values']['securesite_type'] as $type => $value) {
      if (empty($value)) {
        unset($form_state['values']['securesite_type'][$type]);
      }
    }
    sort($form_state['values']['securesite_type']);

    $name = $form_state['values']['securesite_guest_name'];
    if ($name && db_query_range("SELECT name FROM {users} WHERE name = :name", 0, 1, array(':name' => $name))->fetchField() == $name) {
      $form_state->setErrorByName('securesite_guest_name', $form_state, t('The name %name belongs to a registered user.', array('%name' => $name)));
    }

  }

  /**
   * {@inheritdoc}
   * Configure access denied page and manage stored guest password.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state['values'];

    $config_securesite = $this->config('securesite.settings');
    $config_site = $this->config('system.site');

    $config_securesite->set('securesite_enabled', $values['securesite_enabled'])
      ->set('securesite_type', $values['securesite_type'])
      ->set('securesite_digest_script', $values['securesite_digest_script'])
      ->set('securesite_password_script', $values['securesite_password_script'])
      ->set('securesite_realm', $values['securesite_realm'])
      ->set('securesite_guest_name', $values['securesite_guest_name'])
      ->set('securesite_guest_pass', $values['securesite_guest_pass'])
      ->set('securesite_login_form', $values['securesite_login_form'])
      ->set('securesite_reset_form', $values['securesite_reset_form'])
      ->save();

    if ($values['securesite_enabled'] != SECURESITE_403 || isset($values['op']) && $values['op'] == t('Reset to defaults')) {
      $config_site->set('page.403', $config_securesite->get('securesite_403'))->save();
      $config_securesite->clear('securesite_403')->save();
    }
    else {
      $config_securesite->set('securesite_403', $config_site->get('page.403'))->save();
      $config_site->set('page.403', $config_site->get('securesite_403'))->save();
    }
    $script = $config_securesite->get('securesite_password_script');
    $realm = $config_securesite->get('securesite_realm');
    $site_path = DrupalKernel::findSitePath(\Drupal::request());

    if (in_array(SECURESITE_DIGEST, $config_securesite->get('securesite_type'))) {
      // If digest authentication was enabled, we may need to do some clean-up.
      $securesite_guest_name = $config_securesite->get('securesite_guest_name');
      if (
        isset($values['op']) && $values['op'] == t('Reset to defaults') || // Values are being reset to defaults.
        !in_array(SECURESITE_DIGEST, $values['securesite_type']) || // Digest authentication is being disabled.
        $realm != $values['securesite_realm'] // Realm has changed.
      ) {
        // Delete all stored passwords.
        exec("$script realm=" . escapeshellarg($realm) . ' op=delete site_path=' . $site_path);
      }
      elseif ($values['securesite_guest_name'] != $securesite_guest_name) {
        // Guest user name has changed. Delete old guest user password.
        exec("$script username=" . escapeshellarg($securesite_guest_name) . ' realm=' . escapeshellarg($realm) . ' op=delete site_path=' . $site_path);
      }
    }
    if (in_array(SECURESITE_DIGEST, $values['securesite_type']) && (!isset($values['op']) || $values['op'] != t('Reset to defaults'))) {
      // If digest authentication is enabled, update guest user password.
      $args = array(
        'username=' . escapeshellarg($values['securesite_guest_name']),
        'pass=' . escapeshellarg($values['securesite_guest_pass']),
        'realm=' . escapeshellarg($realm),
        'op=create',
        'site_path=' . $site_path,
      );
      exec($script . ' ' . implode(' ', $args));
    }

    parent::submitForm($form, $form_state);
  }

}
