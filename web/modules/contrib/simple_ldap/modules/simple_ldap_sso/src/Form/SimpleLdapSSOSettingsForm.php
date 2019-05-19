<?php
/**
 * @file
 * Contains \Drupal\simple_ldap_sso\Form\SimpleLdapSsoSettingsForm
 */

namespace Drupal\simple_ldap_sso\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_ldap\SimpleLdap;

class SimpleLdapSsoSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_ldap_sso_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'simple_ldap.sso',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('simple_ldap.sso');

    $form['sso'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Single Sign-On'),
      '#open' => TRUE,
    );

    $form['sso']['sid_attribute'] = array(
      '#type' => 'select',
      '#title' => $this->t('LDAP Session ID Attribute'),
      '#options' => array('sid'),
      '#default_value' => $config->get('sid_attribute'),
      '#required' => TRUE,
      '#description' => $this->t('Specify the LDAP attribute that will store the session ID.'),
    );

    $form['sso']['encryption_key'] = array(
      '#type' => 'textfield'
    );

    // Advanced settings.
    $form['advanced'] = array(
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#open' => FALSE,
    );

    $form['advanced']['hashing_algorithm'] = array(
      '#type' => 'select',
      '#title' => $this->t('Session ID Hashing Algorithm'),
      '#description' => $this->t('Choose the algorithm that will be used to hash the session ID stored on LDAP.'),
      '#options' => SimpleLdap::hashes(),
      '#default_value' => $config->get('hashing_algorithm'),
    );

    $options = range(0, 20);
    $options[0] = $this->t('Off. Not Recommended.');
    $form['advanced']['flood_limit'] = array(
      '#type' => 'select',
      '#title' => $this->t('Failed SSO Limit'),
      '#description' => $this->t('The limit of failed SSO attempts a user can make from a single IP.'),
      '#options' => $options,
      '#default_value' => $config->get('flood_limit'),
    );

    $form['advanced']['flood_window'] = array(
      '#type' => 'select',
      '#title' => $this->t('Failed SSO Window'),
      '#description' => $this->t('The window of time in which to enforce the above limit. Higher is safer. Lower is more tolerant.'),
      '#options' => array(
        60 => $this->t('One minute'),
        120 => $this->t('Two minutes'),
        300 => $this->t('Five minutes'),
        600 => $this->t('Ten minutes'),
        900 => $this->t('Fifteen minutes'),
        1800 => $this->t('Thirty minutes'),
        3600 => $this->t('One hour'),
        7200 => $this->t('Two hours'),
        18000 => $this->t('Five hours'),
        86400 => $this->t('One day'),
        604800 => $this->t('One week'),
      ),
      '#default_value' => $config->get('flood_window'),
    );
  }
}
