<?php

/**
 * @file
 * Contains \Drupal\session_cache\Form\SessionCacheSettingsForm.
 */

namespace Drupal\session_cache\Form;

use Drupal\system\SystemConfigFormBase;

/**
 * Menu callback and form-builder for session cache configuration settings.
 */
class SessionCacheSettingsForm extends SystemConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'session_cache_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    // Not sure which of these first two lines is better
    //$this->configFactory->get('session_cache.settings')
    $config = config('session_cache.settings');

    $form['storage_method'] = array(
      '#type' => 'radios',
      '#title' => t('Where should user session data be stored?'),
      '#default_value' => $config->get('storage_method') ?: SESSION_CACHE_STORAGE_SESSION,
      '#options' => array(
        SESSION_CACHE_STORAGE_COOKIE  => t("on the user's computer, in a cookie"),
        SESSION_CACHE_STORAGE_DB_CORE => t("on the server, on core's cache database"),
        SESSION_CACHE_STORAGE_SESSION => t('on the server, in $_SESSION memory')
      ),
      '#description' => t('The first two mechanisms will NOT write to or read from $_SESSION so are generally a good choice when your site uses Varnish or similar page caching engine.')
    );

    $expire_period = (float) $config->get('expire_period');
    if ($expire_period <= 0.0) {
      $expire_period = SESSION_CACHE_DEFAULT_EXPIRATION_DAYS;
    }
    $form['expire_period'] = array(
      '#type' => 'textfield',
      '#size' => 4,
      '#title' => t('Expiration time for the database cache and cookies created via this module'),
      '#field_suffix' => t('days'),
      '#default_value' => $expire_period,
      '#description' => t('You may use decimals, eg 0.25 equates to 6 hours.<br/>$_SESSION expiration is set via the server configuration. See the <em>sites/default/settings.php</em> file for details.')
    );

    $form['use_uid_as_sid'] = array(
      '#type' => 'checkbox',
      '#title' => t("Remember the user's session from one browser to the next"),
      '#default_value' => $config->get('use_uid_as_sid') ?: FALSE,
      '#description' => t('Applies to authenticated users only and does not work for the cookie and $_SESSION storage mechanisms.')
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // Not sure which of these first two lines is better
    //$this->configFactory->get('session_cache.settings')
    config('session_cache.settings')
      ->set('storage_method', $form_state['values']['storage_method'])
      ->set('expire_period',  $form_state['values']['expire_period'])
      ->set('use_uid_as_sid', $form_state['values']['use_uid_as_sid'])
      ->save();

    parent::submitForm($form, $form_state);
  }
}
