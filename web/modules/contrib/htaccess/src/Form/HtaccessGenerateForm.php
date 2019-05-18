<?php

/**
 * @file
 * Administration pages.
 */

/**
 * Admin settings.
 */

namespace Drupal\htaccess\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form to configure RSVP List module settings
 */
class HtaccessGenerateForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'htaccess_admin_generate';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'htaccess.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {

    $form['htaccess_settings_generate_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Generate Htaccess File'),
    '#description' => t('The htaccess\' settings will be based on what you entered in the settings tab.'),
    );

    $form['htaccess_settings_generate_profile'] = array(
    '#type' => 'fieldset',
    '#title' => t('Profile'),
    );

    $form['htaccess_settings_generate_profile']['htaccess_settings_generate_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#description' => t('Name of the htaccess profile: must be lowercase and without any special character.'),
    '#default_value' => '',
    '#required' => TRUE,
    );

    $form['htaccess_settings_generate_profile']['htaccess_settings_generate_description'] = array(
    '#type' => 'textfield',
    '#title' => t('Description'),
    '#description' => t('A short description of the htaccess usage.'),
    '#default_value' => '',
    );

    return parent::buildForm($form,$form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $profile_name = $form_state->getValue('htaccess_settings_generate_name');
    $value = $form_state->getValue('email');

    if(preg_match('/[^a-z0-9]/', $profile_name)) {
       $form_state->setErrorByName('name', t('The name of the profile must be lowercase and without any special character.'));
    }

    // The name of the profile must be unique
    $select = Database::getConnection()->select('htaccess', 'h');
    $select->fields('h');
    $select->condition('name', $profile_name);

    $results = $select->execute();

    if (!empty($results->fetchCol())) {
      // We found a row with this name.
      $form_state->setErrorByName('name', t('The profile %profile already exists.', array('%profile' => $profile_name)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('htaccess.settings');

    \Drupal::moduleHandler()->invokeAll("htaccess_generate_before");

    $htaccess_template = file_get_contents(HTACCESS_TEMPLATE_PATH);

    $rules_before_config = $config->get('htaccess_settings_custom_settings');

    $redirection_config = $config->get('htaccess_settings_url_prefix_redirection');

    $ssl_config = ($config->get('htaccess_settings_ssl') == 'HTTPS_mixed_mode' ? "%{ENV:protossl}" : "s");

    $boot_rules = $config->get('htaccess_settings_boost_module_rules');

    switch ($redirection_config) {
      case 'without_www':
        $without_www_config = "RewriteCond %{HTTP_HOST} ^www\.(.+)$ [NC]" . PHP_EOL;
        $without_www_config.= "RewriteRule ^ http". $ssl_config ."://%1%{REQUEST_URI} [L,R=301]" . PHP_EOL;
        $with_www_config = "#RewriteCond %{HTTP_HOST} ." . PHP_EOL;
        $with_www_config .= "#RewriteCond %{HTTP_HOST} !^www\. [NC]" . PHP_EOL;
        $with_www_config .= "#RewriteRule ^ http". $ssl_config ."://www.%{HTTP_HOST}%{REQUEST_URI} [L,R=301]" . PHP_EOL;
        break;
      case 'with_www':
        $without_www_config = "#RewriteCond %{HTTP_HOST} ^www\.(.+)$ [NC]" . PHP_EOL;
        $without_www_config.= "#RewriteRule ^ http". $ssl_config ."://%1%{REQUEST_URI} [L,R=301]" . PHP_EOL;
        $with_www_config = "RewriteCond %{HTTP_HOST} ." . PHP_EOL;
        $with_www_config .= "RewriteCond %{HTTP_HOST} !^www\. [NC]" . PHP_EOL;
        $with_www_config .= "RewriteRule ^ http". $ssl_config ."://www.%{HTTP_HOST}%{REQUEST_URI} [L,R=301]" . PHP_EOL;
        break;
      default:
        $without_www_config = "#RewriteCond %{HTTP_HOST} ^www\.(.+)$ [NC] " . PHP_EOL;
        $without_www_config.= "#RewriteRule ^ http". $ssl_config ."://%1%{REQUEST_URI} [L,R=301]" . PHP_EOL;
        $with_www_config = "#RewriteCond %{HTTP_HOST} ." . PHP_EOL;
        $with_www_config .= "#RewriteCond %{HTTP_HOST} !^www\. [NC]" . PHP_EOL;
        $with_www_config .= "#RewriteRule ^ http". $ssl_config ."://www.%{HTTP_HOST}%{REQUEST_URI} [L,R=301]" . PHP_EOL;
        break;
    }

    $symbolic_links_config = $config->get('htaccess_settings_symlinks');

    switch ($symbolic_links_config) {
      case 'FollowSymLinks':
        $symbolic_links_config = "+FollowSymLinks";
        break;
      case 'SymLinksifOwnerMatch':
        $symbolic_links_config = "+SymLinksifOwnerMatch";
        break;
      default:
        $symbolic_links_config = "+FollowSymLinks";
        break;
    }

    $ssl_force_redirect_rules = "# Force redirect HTTPS." . PHP_EOL;
    $ssl_force_redirect_rules .= "RewriteCond %{HTTPS} off" . PHP_EOL;
    $ssl_force_redirect_rules .= "RewriteCond %{HTTP:X-Forwarded-Proto} !https" . PHP_EOL;
    $ssl_force_redirect_rules .= "RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]" . PHP_EOL;

    $ssl_force_redirect = ($config->get('htaccess_settings_ssl') == 'HTTPS_mixed_mode' ? NULL : $ssl_force_redirect_rules);

    $search = array("%%%rules_before%%%", "%%%symbolic_links%%%", "%%%ssl_force_redirect%%%", "%%%with_www%%%", "%%%without_www%%%", "%%%boost_rules%%%");
    $replace = array($rules_before_config, $symbolic_links_config, $ssl_force_redirect, $with_www_config, $without_www_config, $boot_rules);

    $htaccess = str_replace($search, $replace, $htaccess_template);

    $htaccess_profile_name = $form_state->getValue('htaccess_settings_generate_name');
    $htaccess_description = $form_state->getValue('htaccess_settings_generate_description');

    $insert = Database::getConnection()->insert('htaccess');
    $insert->fields(array(
      'name' => $htaccess_profile_name,
      'description' => $htaccess_description,
      'htaccess' => $htaccess,
      'created' => REQUEST_TIME,
    ));
    $insert->execute();

    \Drupal::moduleHandler()->invokeAll("htaccess_generate_after", [$htaccess]);

    drupal_set_message(t('A new htaccess profile has been generated.'));
    
    return new RedirectResponse(\Drupal::url('htaccess.admin_deployment'));
  }
}
