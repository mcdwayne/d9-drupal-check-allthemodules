<?php

namespace Drupal\onelogin_integration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class OneLoginSAMLAdminForm.
 *
 * @package Drupal\onelogin_integration\Form
 */
class OneLoginIntegrationAdminForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'onelogin_admin_form';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['onelogin_integration.settings'];
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['onelogin_integration'] = [
      '#type'  => 'fieldset',
      '#title' => t('OneLogin Integration - SSO Settings'),
    ];

    $form['onelogin_integration']['info'] = [
      '#markup' => t('Configure your SAML Service Provider below. Once configured, you can access the metadata 
      <a target="_blank" href="@metadata">here</a>.<br>Further information for OneLogin customers can be found <a target="_blank" href="https://onelogin.zendesk.com/hc/en-us/articles/201173604-Configuring-SAML-for-Drupal">here</a>.', ['@metadata' => Url::fromRoute('onelogin_integration.metadata')->toString()]),
    ];

    // The Identity Provider settings.
    $form['onelogin_integration_idp'] = [
      '#type'  => 'fieldset',
      '#title' => t('Identity Provider settings'),
    ];

    $form['onelogin_integration_idp']['info'] = [
      '#markup' => t('<p>Add information regarding your IdP.</p>'),
    ];

    $form['onelogin_integration_idp']['entityid'] = [
      '#type'          => 'textfield',
      '#title'         => t('Identity Provider (IdP) Entity Id'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('entityid'),
      '#description'   => t('Identifier of the IdP entity. ("Issuer URL")'),
      '#required'      => TRUE,
    ];

    $form['onelogin_integration_idp']['sso'] = [
      '#type' => 'textfield',
      '#title'         => t('Single Sign On Service Url'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('sso'),
      '#description'   => t('URL target of the IdP where the SP will 
      send the Authentication Request. If your IdP has multiple URL targets, 
      the one that uses the HTTP Redirect Binding should be used here. 
      ("SAML 2.0 Endpoint (HTTP)")'),
      '#required'      => TRUE,
    ];

    $form['onelogin_integration_idp']['slo_option'] = [
      '#type'          => 'checkbox',
      '#title'         => t('<strong>Single Log Out</strong>'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('slo_option'),
      '#description'   => t('Enable SAML Single Log Out. SLO is complex functionality. The most common SLO implementation is based on front-channel (redirections). Sometimes if the SLO workflow fails, a user can be blocked in an unhandled view. Unless you have a strong grasp of SLO it is recommended that you leave it disabled. If enabled, enter the IdP"s SLO target URL below.'),
      '#required'      => FALSE,
    ];

    $form['onelogin_integration_idp']['slo'] = [
      '#type'          => 'textfield',
      '#title'         => t('Single Log Out Service Url'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('slo'),
      '#description'   => t('URL target for the IdP where the SP will send the SLO Request. ("SLO Endpoint (HTTP)")'),
      '#required'      => FALSE,
    ];

    $form['onelogin_integration_idp']['logout_link'] = [
      '#type'          => 'textfield',
      '#title'         => t('Logout Redirect'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('logout_link'),
      '#description'   => t('If Single Log Out is not used, you can choose to redirect a SAML user after they are logged out of Drupal. Some use this to redirect to an IdP logout page, a Central Authentication Service (CAS) logout page, or a custom page warning the user to close their browser to end their SSO session. This only affects users who have logged in via SAML.'),
      '#required'      => FALSE,
    ];

    $form['onelogin_integration_idp']['x509cert'] = [
      '#type'          => 'textarea',
      '#title'         => t('X.509 Certificate'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('x509cert'),
      '#description'   => t('Public x509 certificate of the IdP. The full certificate (including -----BEGIN CERTIFICATE----- and -----END CERTIFICATE-----) is required. ("X.509 certificate")'),
      '#required'      => TRUE,
    ];

    // The options settings.
    $form['onelogin_integration_options'] = [
      '#type'  => 'fieldset',
      '#title' => t('Options'),
    ];

    $form['onelogin_integration_options']['info'] = [
      '#markup' => t('<p>In this section the behavior of the plugin is set.</p>'),
    ];

    $form['onelogin_integration_options']['username_from_email'] = [
      '#type'          => 'checkbox',
      '#title'         => t('<strong>Get username from email address</strong>'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('username_from_email'),
      '#description'   => t('<p>Use everything in front of the @ in the email address as the username. This may be useful if you are only sending an email address in your SAML response, but you want to auto-provision accounts which requires a username and email address.</p>'),
      '#required'      => FALSE,
    ];

    $form['onelogin_integration_options']['saml_link'] = [
      '#type'          => 'checkbox',
      '#title'         => t('<strong>SAML link</strong>'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('saml_link'),
      '#description'   => t('<p>Show or not a SAML link to execute a SP-initiated SSO in the login page</p>'),
      '#required'      => FALSE,
    ];

    $form['onelogin_integration_options']['account_matcher'] = [
      '#type'          => 'select',
      '#title'         => t('Match Drupal account by'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('account_matcher'),
      '#options'       => ['username', 'email'],
      '#description'   => t('Select what field will be used in order to find the user account. If you select the <i>email</i> fieldname remember to prevent that the user is able to change his mail in his profile.'),
    ];

    // The attribute mapping.
    $form['onelogin_integration_mapping'] = [
      '#type'  => 'fieldset',
      '#title' => t('Attribute mapping'),
    ];

    $form['onelogin_integration_mapping']['info'] = [
      '#markup' => t('<p>Sometimes the names of the attributes sent by the IdP not match the names used by Drupal for the user accounts. In this section we can set the mapping between IdP fields and Drupal fields. Notice that this mapping could be also set at Onelogin"s IdP.</p>'),
    ];

    $form['onelogin_integration_mapping']['username'] = [
      '#type'          => 'textfield',
      '#title'         => t('Username'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('username'),
      '#description'   => t("Be sure that usernames at the IdP don't contain punctuation (periods, hyphens, apostrophes, and underscores are allowed)"),
      '#required'      => TRUE,
    ];

    $form['onelogin_integration_mapping']['email'] = [
      '#type'          => 'textfield',
      '#title'         => t('E-mail'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('email'),
      '#required'      => TRUE,
    ];

    $form['onelogin_integration_mapping']['role'] = [
      '#type'          => 'textfield',
      '#title'         => t('Role'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('role'),
      '#required'      => FALSE,
    ];

    $form['onelogin_integration_mapping']['onelogin_role_delimiter'] = [
      '#type' => 'textfield',
      '#title' => t('Role delimiter'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('onelogin_role_delimiter'),
      '#description' => t('The delimiter used to seperate the roles in the list of roles coming from OneLogin'),
      '#required' => FALSE,
    ];

    // The role mapping.
    $form['onelogin_integration_role_mapping'] = [
      '#type'  => 'fieldset',
      '#title' => t('Role mapping'),
    ];

    $form['onelogin_integration_role_mapping']['administrator_info'] = [
      '#markup' => t('<p>The IdP can use it"s own roles. Set the mapping between IdP and Drupal roles in this section. Accepts multiple values that <strong><i>must be</i></strong> comma separated. Example: admin,owner,superuser.</p>'),
    ];

    // Get all roles in the site.
    $roles = user_role_names();

    // No mapping is needed for anonymous or authenticated roles.
    unset($roles['anonymous']);
    unset($roles['authenticated']);

    // Sort the roles alphabetically for nicer presentation.
    ksort($roles);

    // For every role in the site, create an input field.
    foreach ($roles as $role_machine_name => $role_label) {
      $form['onelogin_integration_role_mapping']['role_' . $role_machine_name] = [
        '#type'          => 'textfield',
        '#title'         => $role_label,
        '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('role_' . $role_machine_name),
        '#required'      => FALSE,
      ];
    }

    // The user experience.
    $form['onelogin_integration_user_experience'] = [
      '#type'  => 'fieldset',
      '#title' => t('User experience'),
    ];

    $form['onelogin_integration_user_experience']['info'] = [
      '#markup' => t('<p>When implementing SSO, our users may become confused with menus and links that allow them to manage a local Drupal password or request a new account. These options allow you to customize the experience for SAML users with the hopes of avoiding some of the confusion.</p>'),
    ];

    $form['onelogin_integration_user_experience']['current_pass_disabled'] = [
      '#type'          => 'checkbox',
      '#title'         => t('<strong>Disable current password field on user profile page.</strong>'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('current_pass_disabled'),
      '#description'   => t('<p>You may wish to limit a user from creating and managing a Drupal password. The user profile form includes a current password field that is required as validation in order to update certain user profile fields (such as email address). If the user does not have a Drupal password, this will get in the way. This option disables the field for users who have logged in via SAML. Users with the Administrator role are exempt.</p>'),
      '#required'      => FALSE,
    ];

    $form['onelogin_integration_user_experience']['password_tab_disabled'] = [
      '#type'          => 'checkbox',
      '#title'         => t('<strong>Disable user password tab and related page.</strong>'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('password_tab_disabled'),
      '#description'   => t('<p>You may wish to limit a user from creating and managing a Drupal password. This option disables the menu tabs associated with the user password page. This option disables the password page for users who have logged in via SAML. Users with the Administrator role are exempt.</p>'),
      '#required'      => FALSE,
    ];

    $form['onelogin_integration_user_experience']['email_field_disabled'] = [
      '#type'          => 'checkbox',
      '#title'         => t('<strong>Disable user e-mail field on user profile page.</strong>'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('email_field_disabled'),
      '#description'   => t('You may wish to limit a user from managing a Drupal e-mail. This option disables the e-mail field for users who have logged in via SAML. Users with the Administrator role are exempt.'),
      '#required'      => FALSE,
    ];

    $form['onelogin_integration_user_experience']['create_new_account'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customize the Create new account link.'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('create_new_account'),
      '#description'   => t('Depending on your Drupal implementation, you may allow requests for new accounts from the Drupal login page. Rather than using Drupal"s request form, you can direct users to your company"s account request form.'),
      '#required'      => FALSE,
    ];

    $form['onelogin_integration_user_experience']['request_new_password'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customize the Request new password link.'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('request_new_password'),
      '#description'   => t('If you have enabled the Request new password link in Drupal, a SSO user could click the link and go through the process believing that their SSO account password is being changed. In reality this would only change their local Drupal password. To avoid this confusion you can direct users to your company"s password management system.'),
      '#required'      => FALSE,
    ];

    // The advanced settings.
    $form['onelogin_integration_advanced_settings'] = [
      '#type'  => 'fieldset',
      '#title' => t('Advanced settings'),
    ];

    $form['onelogin_integration_advanced_settings']['debug'] = [
      '#type'          => 'checkbox',
      '#title'         => t('<strong>Debug Mode</strong>'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('debug'),
      '#description'   => t('Enable it when you are debugging the SAML workflow. Errors and Warnigs will be showed.'),
      '#required'      => FALSE,
    ];

    $form['onelogin_integration_advanced_settings']['strict_mode'] = [
      '#type'          => 'checkbox',
      '#title'         => t('<strong>Strict Mode</strong>'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('strict_mode'),
      '#description'   => t('If Strict mode is Enabled, then Drupal will reject unsigned or unencrypted messages if it expects them signed or encrypted. Also it will reject the messages if they do not strictly follow the SAML standard: Destination, NameId, Conditions ... are validated too.'),
      '#required'      => FALSE,
    ];

    $form['onelogin_integration_advanced_settings']['sp_entity_id'] = [
      '#type'          => 'textfield',
      '#title'         => t('Service Provider Entity Id'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('sp_entity_id'),
      '#description'   => t('Set the Entity ID for the Service Provider. If not provided, <i>php-saml</i> will be used.'),
      '#required'      => FALSE,
    ];

    $form['onelogin_integration_advanced_settings']['nameid_format'] = [
      '#type'          => 'textfield',
      '#title'         => t('NameId Format'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('nameid_format'),
      '#description'   => t('Set the NameId format that the Service Provider and Identity Provider will use. If not provided, <i>urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress</i>will be used.'),
      '#required'      => FALSE,
    ];

    // Signing/encryption settings.
    $form['onelogin_integration_signing_encryption_settings'] = [
      '#type'  => 'fieldset',
      '#title' => t('Signing/encryption settings'),
    ];

    $form['onelogin_integration_signing_encryption_settings']['info'] = [
      '#markup' => t('<p>If signing/encryption is enabled, then a x509 cert and private key for the SP must be provided. There are two ways to supply the certificate and key:</p><p>1. Store them as files named sp.key and sp.crt in the <i>certs</i> folder of this Drupal module (be sure that the folder is protected and not exposed to the Internet).<br>2. Paste the certificate and key text in the corresponding textareas (review any database security issues as to limit the exposure of the key).</p><p><strong>Please be aware: if you encrypt the entire SAML Assertion, this module will not be able to decrypt attributes. Much of the functionality of this module depends on attributes (auto-provisioning, role sync, etc.). If you can live without encrypting the entire SAML Assertion, your attributes will work and additional security can be implemented by encrypting the NameId and enforcing signed requests/responses.</strong></p>'),
    ];

    $form['onelogin_integration_signing_encryption_settings']['nameid_encrypted'] = [
      '#type'          => 'checkbox',
      '#title'         => t('<strong>Encrypt nameID</strong>'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('nameid_encrypted'),
      '#description'   => t('The nameID sent by this SP will be encrypted.'),
      '#required'      => FALSE,
    ];

    $form['onelogin_integration_signing_encryption_settings']['authn_request_signed'] = [
      '#type'          => 'checkbox',
      '#title'         => t('<strong>Sign AuthnRequest</strong>'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('authn_request_signed'),
      '#description'   => t('The samlp:AuthnRequest messages sent by this SP will be signed.'),
      '#required'      => FALSE,
    ];

    $form['onelogin_integration_signing_encryption_settings']['logout_request_signed'] = [
      '#type'          => 'checkbox',
      '#title'         => t('<strong>Sign LogoutRequest</strong>'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('logout_request_signed'),
      '#description'   => t('The samlp:logoutRequest messages sent by this SP will be signed.'),
      '#required'      => FALSE,
    ];

    $form['onelogin_integration_signing_encryption_settings']['logout_response_signed'] = [
      '#type'          => 'checkbox',
      '#title'         => t('<strong>Sign LogoutResponse</strong>'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('logout_response_signed'),
      '#description'   => t('The samlp:logoutResponse messages sent by this SP will be signed.'),
      '#required'      => FALSE,
    ];

    $form['onelogin_integration_signing_encryption_settings']['want_message_signed'] = [
      '#type'          => 'checkbox',
      '#title'         => t('<strong>Reject Unsigned Messages</strong>'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('want_message_signed'),
      '#description'   => t('Reject unsigned samlp:Response, samlp:LogoutRequest and samlp:LogoutResponse received'),
      '#required'      => FALSE,
    ];

    $form['onelogin_integration_signing_encryption_settings']['want_assertion_signed'] = [
      '#type'          => 'checkbox',
      '#title'         => t('<strong>Reject unsigned saml:Assertion received</strong>'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('want_assertion_signed'),
      '#description'   => t('Reject Unsigned Assertions'),
      '#required'      => FALSE,
    ];

    $form['onelogin_integration_signing_encryption_settings']['want_assertion_encrypted'] = [
      '#type'          => 'checkbox',
      '#title'         => t('<strong>Reject Unencrypted Assertions</strong>'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('want_assertion_encrypted'),
      '#description'   => t('Reject unencrypted saml:Assertion received.'),
      '#required'      => FALSE,
    ];

    $form['onelogin_integration_signing_encryption_settings']['sp_x509cert'] = [
      '#type'          => 'textarea',
      '#title'         => t('Service Provider X.509 Certificate'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('sp_x509cert'),
      '#description'   => t('Public x509 certificate of the SP. The full certificate (including -----BEGIN CERTIFICATE----- and -----END CERTIFICATE-----) is required. Leave this field empty if you have added sp.crt to the certs folder of this module.'),
      '#required'      => FALSE,
    ];

    $form['onelogin_integration_signing_encryption_settings']['sp_privatekey'] = [
      '#type'          => 'textarea',
      '#title'         => t('Service Provider Private Key'),
      '#default_value' => $this->configFactory->get('onelogin_integration.settings')->get('sp_privatekey'),
      '#description'   => t('Private Key of the SP. The full certificate (including -----BEGIN CERTIFICATE----- and -----END CERTIFICATE-----) is required. Leave this field empty if have added sp.key to the certs folder of this module.'),
      '#required'      => FALSE,
    ];

    $form['actions']['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * Submits the form.
   *
   * @param array $form
   *   The form itself.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('onelogin_integration.settings');

    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }

    $config->save(TRUE);

    parent::submitForm($form, $form_state);
  }

}
