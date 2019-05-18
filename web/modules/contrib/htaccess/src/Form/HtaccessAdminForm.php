<?php

/**
 * @file
 * Administration pages.
 */

/**
 * Admin settings.
 */

namespace Drupal\htaccess\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form to configure RSVP List module settings
 */
class HtaccessAdminForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'htaccess_admin_settings';
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
    $config = $this->config('htaccess.settings');

    $form['htaccess_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('General'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );

    $htaccess_settings_url_prefix_redirection_options = array(
      'without_www' => t('Without WWW prefix'),
      'with_www' => t('With WWW prefix'),
    );

    $form['htaccess_settings']['htaccess_settings_url_prefix_redirection'] = array(
      '#type' => 'radios',
      '#title' => t('URL prefix redirection'),
      '#description' => t('Use one of the following options to redirect users to your preferred
      URL, either <strong>with</strong> or <strong>without</strong> the \'www.\' prefix. Default: without.'),
      '#options' => $htaccess_settings_url_prefix_redirection_options,
      '#default_value' => $config->get('htaccess_settings_url_prefix_redirection'),
    );

    $htaccess_settings_symlinks_options = array(
      'FollowSymLinks' => t('+FollowSymLinks'),
      'SymLinksifOwnerMatch' => t('+SymLinksifOwnerMatch'),
    );

    $form['htaccess_settings']['htaccess_settings_symlinks'] = array(
      '#type' => 'radios',
      '#title' => t('Symbolic links'),
      '#description' => t('Define the Apache\'s right options to access to parts of the filesystem. Default: +FollowSymLinks.<br />For more informations, see <a href="@link_apache" target="_blank">http://httpd.apache.org/docs/2.2/urlmapping.html#outside</a>.', array('@link_apache' => \Drupal\Core\Url::fromUri('http://httpd.apache.org/docs/2.2/urlmapping.html#outside'))),
      '#options' => $htaccess_settings_symlinks_options,
      '#default_value' => $config->get('htaccess_settings_symlinks'),
    );

    $htaccess_settings_ssl_options = array(
      'HTTPS_mixed_mode' => t('Enable mixed-mode HTTP/HTTPS (allow trafic from both HTTP and HTTPS'),
      'HTTPS_force_redirect' => t('Enable HTTPS and redirect all HTTP trafic (force all trafic through HTTPS protocol only)'),
    );

    $form['htaccess_settings']['htaccess_settings_ssl'] = array(
      '#type' => 'radios',
      '#title' => t('HTTP Secure (HTTPS)'),
      '#description' => t('Before activating the HTTPS support, you should first get a valid certificate, then configure your web server.<br />For more informations, see <a href="@link_ssl" target="_blank">https://www.drupal.org/https-information</a>.', array('@link_ssl' => \Drupal\Core\Url::fromUri('https://www.drupal.org/https-information'))),
      '#options' => $htaccess_settings_ssl_options,
      '#default_value' => $config->get('htaccess_settings_ssl'),
    );

    $form['htaccess_settings_custom'] = array(
      '#type' => 'fieldset',
      '#title' => t('Custom settings'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );

    $form['htaccess_settings_custom']['htaccess_settings_custom_settings'] = array(
      '#type' => 'textarea',
      '#description' => t('Copy/paste below your custom settings (redirections, rewrite rules etc..). These will be added before the Drupal rules.'),
      '#default_value' => $config->get('htaccess_settings_custom_settings'),
    );

    $form['htaccess_settings_boost_module'] = array(
      '#type' => 'fieldset',
      '#title' => t('Boost'),
      '#description' => t('The Boost module is a static file caching tool to improve performance.'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );

    $htaccess_settings_boost_module_readonly = (!\Drupal::moduleHandler()->moduleExists('boost') ? TRUE : FALSE);
    $htaccess_settings_boost_module_description = ($htaccess_settings_boost_module_readonly ? t('Boost is currently not installed. You can download it at <a href="https://drupal.org/project/boost" target="_blank">https://drupal.org/project/boost</a>.') : t('If enabled, copy and paste the <a href="admin/config/system/boost">Boost rules</a>.'));


    $form['htaccess_settings_boost_module']['htaccess_settings_boost_module_rules'] = array(
      '#type' => 'textarea',
      '#title' => t('Rules'),
      '#description' => $htaccess_settings_boost_module_description,
      '#default_value' => $config->get('htaccess_settings_boost_module_rules'),
      '#disabled' => $htaccess_settings_boost_module_readonly,
    );

    return parent::buildForm($form,$form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $htaccess_settings_url_prefix_redirection = $form_state->getValue('htaccess_settings_url_prefix_redirection');
    $htaccess_settings_symlinks = $form_state->getValue('htaccess_settings_symlinks');
    $htaccess_settings_ssl = $form_state->getValue('htaccess_settings_ssl');
    $htaccess_settings_custom_settings = $form_state->getValue('htaccess_settings_custom_settings');
    $htaccess_settings_boost_module_rules = $form_state->getValue('htaccess_settings_boost_module_rules');

    $this->config('htaccess.settings')
      ->set('htaccess_settings_url_prefix_redirection', $htaccess_settings_url_prefix_redirection)
      ->set('htaccess_settings_symlinks', $htaccess_settings_symlinks)
      ->set('htaccess_settings_ssl', $htaccess_settings_ssl)
      ->set('htaccess_settings_custom_settings', $htaccess_settings_custom_settings)
      ->set('htaccess_settings_boost_module_rules', $htaccess_settings_boost_module_rules)
      ->save();
    parent::submitForm($form, $form_state);
  }
}
