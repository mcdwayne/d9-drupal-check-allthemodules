<?php

/**
 * @file
 * Contains \Drupal\wayf_dk_login\Form\WayfSettingsForm.
 */

namespace Drupal\wayf_dk_login\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class WayfSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wayf_dk_login_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'wayf_dk_login.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('wayf_dk_login.settings');

    // IdP settings tab
    $form['vertical_tabs_container'] = array(
      '#type' => 'vertical_tabs',
    );

    $form['idp'] = array(
      '#type' => 'details',
      '#title'  => $this->t('WAYF bridge'),
      '#group' => 'vertical_tabs_container',
    );

    $form['idp']['service_mode'] = array(
      '#type' => 'select',
      '#title' => $this->t('Service mode'),
      '#options' => array(
        'test' => $this->t('Test'),
        'qa' => $this->t('Quality assurance'),
        'production' => $this->t('Production'),
      ),
      '#default_value' => $config->get('service_mode'),
    );

    foreach (array('test','qa','production') as $mode) {
      $metadata = wayf_dk_login__get_ipd_metadata($mode);

      $form['idp']['idp_sso_' . $mode] = array(
        '#type' => 'textfield',
        '#attributes' =>  array('disabled' => 'disabled'),
        '#title' => $this->t('Single signon URL'),
        '#value' => $metadata->sso,
        '#states' => array(
          'visible' => array(
            ':input[name="service_mode"]' => array('value' => $mode),
          ),
        ),
      );

      $form['idp']['idp_slo_' . $mode] = array(
        '#type' => 'textfield',
        '#attributes' =>  array('disabled' => 'disabled'),
        '#title' => $this->t('Single logout URL'),
        '#value' => $metadata->slo,
        '#states' => array(
          'visible' => array(
            ':input[name="service_mode"]' => array('value' => $mode),
          ),
        ),
      );

      $form['idp']['idp_certificate_' . $mode] = array(
        '#type' => 'textarea',
        '#attributes' =>  array('disabled' => 'disabled'),
        '#title' => $this->t('Certificate'),
        '#value' => $metadata->cert,
        '#rows' => 18,
        '#states' => array(
          'visible' => array(
            ':input[name="service_mode"]' => array('value' => $mode),
          ),
        ),
      );
    }

    // Service provider settings tab.
    $form['sp'] = array(
      '#type' => 'details',
      '#title'  => $this->t('Service provider'),
      '#group' => 'vertical_tabs_container',
    );

    $form['sp']['sp_entityid'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Connection ID'),
      '#default_value' => $config->get('sp_entityid'),
      '#description' => $this->t('EntityID used for the service.'),
    );

    $form['sp']['sp_endpoint'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('AssertionConsumerService:0:Location'),
      '#default_value' => $config->get('sp_endpoint'),
      '#description' => $this->t('Endpoint URL for the service.'),
    );

    $form['sp']['sp_logout_endpoint'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('SingleLogoutService redirect location'),
      '#default_value' => $config->get('sp_logout_endpoint'),
      '#description' => $this->t('Endpoint URL for the service.'),
    );

    $form['sp']['certificate'] = array(
      '#type' => 'details',
      '#title' => $this->t('Certificate'),
      '#description' => $this->t('The certificate and private key used to communicate and sign message with WAYF.'),
      '#open' => !((bool) $config->get('sp_key')),
    );

    $form['sp']['certificate']['sp_key'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Private key'),
      '#default_value' => $config->get('sp_key'),
      '#description' => $this->t('Private key, base64 PEM formatted. <br>The key should be the data between -----BEGIN RSA PRIVATE KEY----- and -----END RSA PRIVATE KEY-----'),
    );

    $form['sp']['certificate']['sp_cert'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Certificate'),
      '#default_value' => $config->get('sp_cert'),
      '#description' => $this->t('The certificate should be the data between -----BEGIN CERTIFICATE----- and -----END CERTIFICATE-----'),
    );

    $form['sp']['organization'] = array(
      '#type' => 'details',
      '#title' => $this->t('Organizations information'),
      '#open' => TRUE,
    );

    $form['sp']['organization']['sp_organizations_list_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Organizations feed URL'),
      '#default_value' => $config->get('sp_organizations_list_url'),
      '#description' => $this->t('The URL of the JSON feed with organizations.'),
    );

    // Get languages from the feed.
    $options = array();
    $list  = json_decode($config->get('sp_organizations_list'), TRUE);
    if (!empty($list)) {
      $items = array_keys(reset($list));
      $options = array();
      foreach ($items as $item) {
        if ($item == 'schacHomeOrganization') {
          continue;
        }
        $options[$item] = $item;
      }
    }

    $form['sp']['organization']['sp_organizations_name_language'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Organizations name language version'),
      '#default_value' => $config->get('sp_organizations_name_language'),
      '#description' => $this->t('The language code of the orginazation names to use. If empty, run cron and make sure the feed URL is correct.'),
    );


    $form['sp']['organization']['sp_organizations_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('OrganizationName'),
      '#default_value' => $config->get('sp_organizations_name'),
    );

    $form['sp']['organization']['sp_organizations_displayname'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('OrganizationDisplayName'),
      '#default_value' => $config->get('sp_organizations_displayname'),
    );

    $form['sp']['organization']['sp_organizations_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('OrganizationURL'),
      '#default_value' => $config->get('sp_organizations_url'),
    );

    $form['sp']['contact'] = array(
      '#type' => 'details',
      '#title' => $this->t('Contact information'),
      '#open' => !$config->get('sp_contact_name'),
    );

    $form['sp']['contact']['sp_contact_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('GivenName'),
      '#default_value' => $config->get('sp_contact_name'),
    );

    $form['sp']['contact']['sp_contact_mail'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('EmailAddres'),
      '#default_value' => $config->get('sp_contact_mail'),
    );

    // User settings.
    $form['fields'] = array(
      '#type' => 'details',
      '#title'  => $this->t('Field mappings'),
      '#group' => 'vertical_tabs_container',
      '#description' => '<p>' . $this->t('Notice: only textfields can be assigned attributes
        released from WAYF and currently only singular attributes are supported.
        The email address attribute are mapped to user->mail by default.') . '</p>',
    );

    $mapping = $config->get('mapping');
    $form['user']['field']['mapping'] = array(
      '#tree' => TRUE,
    );

    $fields_info = \Drupal::entityManager()->getFieldDefinitions('user', 'user');
    foreach ($fields_info as $field_name => $field_info) {
      if ($field_info->getType() == 'text') {
        $form['fields']['mapping'][$field_name] = array(
          '#type' => 'select',
          '#options' => $this->attribute_options(),
          '#title' => SafeMarkup::checkPlain($field_info->getLabel()),
          '#default_value' => $mapping[$field_name],
        );
      }
    }

    // Metadata field set.
    $form['metadata'] = array(
      '#type' => 'details',
      '#title' => $this->t('Metadata (SP)'),
      '#group' => 'vertical_tabs_container',
    );

    $form['metadata']['container'] = array(
      '#type' => 'container',
      '#prefix' => '<div id="idp-wrapper">',
      '#suffix' => '</div>',
    );

    global $base_url;
    $form['metadata']['container']['url'] = array(
      '#type' => 'markup',
      '#markup' => '<p><strong>Metadata url:</strong> <a target="_blank" href="' . $base_url . '/wayf/metadata">' .  $base_url . '/wayf/metadata' . '</a></p>',
    );

    $form['metadata']['container']['data'] = array(
      '#type' => 'markup',
      '#markup' => '<p>Metadata generated based on the information entered under the "Service provider" tab.</br><div class="sp-metadata"><pre>' . htmlentities(wayf_dk_login__generate_metadata()) . '</pre></div></p>',
    );

    // User settings.
    $form['display'] = array(
      '#type' => 'details',
      '#title'  => $this->t('Display settings'),
      '#group' => 'vertical_tabs_container',
    );

    $form['display']['alter_login_form'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add WAYF login button to the standard login form'),
      '#default_value' => $config->get('alter_login_form'),
    );

    $icon_path = drupal_get_path('module', 'wayf_dk_login') . '/icons/';

    $icons = array();
    foreach (wayf_dk_login__icons() as $icon) {
      $icons[$icon] = array(
        'icon' => '<img src="/' . $icon_path . $icon .'">',
      );
    }

    $form['display']['icon'] = array(
      '#type' => 'tableselect',
      '#multiple' => FALSE,
      '#options' => $icons,
      '#header' => array('icon' => $this->t('Icon')),
      '#default_value' => $config->get('icon'),
    );

    // Scoping field set.
    $form['scoping'] = array(
      '#type' => 'details',
      '#title' => $this->t('Organizations (scoping)'),
      '#group' => 'vertical_tabs_container',
    );

    $list = json_decode($config->get('sp_organizations_list'), TRUE);
    if (!empty($list)) {
      $language = $config->get('sp_organizations_name_language');
      $options = array();
      foreach ($list as $key => $value) {
        $options[$value['schacHomeOrganization']] = $value[$language];
      }
      asort($options);
    }

    $active_list = json_decode($config->get('sp_organizations_active'));
    $form['scoping']['sp_organizations_active'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed organizations'),
      '#options' => $options,
      '#default_value' => $active_list,
      '#description' => $this->t('Select the organizations whose users should be able to log in. If none are checked, users from everywhere can log in.'),
    );

    // Settings field set.
    $form['settings'] = array(
      '#type' => 'details',
      '#title' => t('Settings'),
      '#group' => 'vertical_tabs_container',
    );

    $form['settings']['user'] = array(
      '#type' => 'fieldset',
      '#title' => t('User creating process'),
      '#description' => t('Select which modules should be used to create the Drupal user after login to WAYF have succesfull completed. Note that order in which the modules are called is by system weight.'),
    );

    $options = array();
    $hook = 'wayf_dk_login_create_user';
    foreach (\Drupal::moduleHandler()->getImplementations($hook) as $module) {
      $options[$module] = $module;
    }

    $form['settings']['user']['user_create_modules'] = array(
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $config->get('user_create_modules'),
    );

    $form['settings']['redirects'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Redirects'),
    );

    $form['settings']['redirects']['login_redirect'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('The url to redirect to after login.'),
      '#default_value' => $config->get('login_redirect'),
    );

    $form['settings']['development'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Development'),
    );

    $form['settings']['development']['development_log_auth_data'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Log authentication requests'),
      '#default_value' => $config->get('development_log_auth_data'),
      '#description' => $this->t('Log authentication data including attributes. This can be useful for debugging.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mode = $form_state->getValue('service_mode');

    $this->config('wayf_dk_login.settings')
      ->set('service_mode', $mode)
      ->set('idp_sso', $form_state->getValue('idp_sso_' . $mode))
      ->set('idp_slo', $form_state->getValue('idp_slo_' . $mode))
      ->set('idp_certificate', $form_state->getValue('idp_certificate_' . $mode))
      ->set('sp_entityid', $form_state->getValue('sp_entityid'))
      ->set('sp_endpoint', $form_state->getValue('sp_endpoint'))
      ->set('sp_logout_endpoint', $form_state->getValue('sp_logout_endpoint'))
      ->set('sp_key', $form_state->getValue('sp_key'))
      ->set('sp_cert', $form_state->getValue('sp_cert'))
      ->set('sp_organizations_name_language', $form_state->getValue('sp_organizations_name_language'))
      ->set('sp_organizations_name', $form_state->getValue('sp_organizations_name'))
      ->set('sp_organizations_displayname', $form_state->getValue('sp_organizations_displayname'))
      ->set('sp_organizations_url', $form_state->getValue('sp_organizations_url'))
      ->set('sp_contact_name', $form_state->getValue('sp_contact_name'))
      ->set('sp_contact_mail', $form_state->getValue('sp_contact_mail'))
      ->set('mapping',$form_state->getValue('mapping', array()))
      ->set('sp_organizations_active', json_encode($form_state->getValue('sp_organizations_active')))
      ->set('alter_login_form', $form_state->getValue('alter_login_form'))
      ->set('icon', $form_state->getValue('icon'))
      ->set('user_create_modules', $form_state->getValue('user_create_modules'))
      ->set('login_redirect', $form_state->getValue('login_redirect'))
      ->set('development_log_auth_data', $form_state->getValue('development_log_auth_data', FALSE))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Function attribute_options returns list of WAYF attributes.
   *
   * @return array
   *   list of singular attributes
   */
  private function attribute_options() {
    return array(
      '' => $this->t('Not mapped'),
      'urn:oid:2.5.4.4'  => $this->t('Last name'),
      'urn:oid:2.5.4.42' => $this->t('First name'),
      'urn:oid:2.5.4.3'  => $this->t('Nickname'),
      'urn:oid:2.5.4.10' => $this->t('Organisation nickname'),
      'urn:oid:1.3.6.1.4.1.5923.1.1.1.5' => $this->t('Primary user affiliation'),
    );
  }

}
