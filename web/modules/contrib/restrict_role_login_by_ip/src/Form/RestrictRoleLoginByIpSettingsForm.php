<?php

/**
 * @file
 * Contains \Drupal\restrict_role_login_by_ip\Form\RestrictRoleLoginByIpSettingsForm.
 */

namespace Drupal\restrict_role_login_by_ip\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure restrict role login by IP settings for this site.
 */
class RestrictRoleLoginByIpSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'restrict_role_login_by_ip_admin_settings';
  }
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['restrict_role_login_by_ip.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $roles = array();
    // Get all roles except anonymous.
    $user_roles = user_roles(TRUE);
    foreach ($user_roles AS $name => $role) {
      $roles[] = $name;
    }
    // Remove default authenticated user role.
    unset($roles[array_search(t('authenticated'), $roles)]);

    $config = $this->config('restrict_role_login_by_ip.settings');

    $form['restrict_role_login_by_ip_header'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Header to check'),
      '#default_value' => $config->get('restrict_role_login_by_ip_header'),
      '#description' => $this->t('This is the HTTP request header that contains the client IP Address. It is sometimes re-written by reverse proxies and Content Distribution Networks. Default Value is  REMOTE_ADDR.'),
    );

    $form['restrict_role_login_by_ip_header_restriction'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Allowed IP range'),
      '#description' => $this->t('Enter IP Address Ranges in CIDR Notation seperated with semi-colons, <b><u>with no trailing semi-colon</u></b>.<br /> E.G. 10.20.30.0/24;192.168.199.1/32;1.0.0.0/8<br />For more information on CIDR notation click <a href="http://www.brassy.net/2007/mar/cidr_basic_subnetting">here</a>.'),
      '#default_value' => $config->get('restrict_role_login_by_ip_header_restriction', ''),
      '#maxlength' => 256,
    );

    $form['restrict_role_login_by_ip_header_roles'] = array(
      '#type' => 'checkboxes',
      '#options' => array_combine($roles, $roles),
      '#title' => $this->t('What Roles do you want to restrict the login (to Whitelisted IPs)?'),
      '#default_value' => $config->get('restrict_role_login_by_ip_header_roles'),
    );

    return parent::buildForm($form, $form_state);
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('restrict_role_login_by_ip.settings')
      ->set('restrict_role_login_by_ip_header', $form_state->getValue('restrict_role_login_by_ip_header'))
      ->set('restrict_role_login_by_ip_header_restriction', $form_state->getValue('restrict_role_login_by_ip_header_restriction'))
      ->set('restrict_role_login_by_ip_header_roles', $form_state->getValue('restrict_role_login_by_ip_header_roles'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
