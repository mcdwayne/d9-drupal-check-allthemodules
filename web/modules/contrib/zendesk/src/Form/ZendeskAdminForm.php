<?php

/**
 * @file
 * Contains \Drupal\zendesk\Form\ZendeskAdminForm.
 */

namespace Drupal\zendesk\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure zendesk settings for this site.
 */
class ZendeskAdminForm extends ConfigFormBase {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'zendesk_admin_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('zendesk.settings');
    $form['zendesk']['zendesk_api'] = array(
      '#type' => 'fieldset',
      '#title' => 'API configuration',
    );

    $form['zendesk']['zendesk_api']['zendesk_url'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => t('Zendesk URL'),
      '#description' => 'The url of your zendesk support page (e.g. http://yourdomain.zendesk.com).',
      '#default_value' => $config->get('zendesk_url'),
    );

    $form['zendesk']['zendesk_api']['zendesk_subdomain'] = array(
      '#type' => 'textfield',
      '#title' => t('Zendesk subdomain'),
      '#default_value' => $config->get('zendesk_subdomain'),
      '#description' => t('The subdomain of your zendesk page: if your zendesk is http://subdomain.zendesk.com, then you have to fil in "subdomain".'),
    );

    $form['zendesk']['zendesk_api']['zendesk_api_token'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#description' => 'The zendesk API token.',
      '#default_value' => $config->get('zendesk_api_token'),
      '#suffix' => t('Use the http://www.yourdomain.com/agent/#/admin/channels page in your zendesk configuration page. (Go to Account -> Channels'),
    );

    $form['zendesk']['zendesk_api']['zendesk_api_mail'] = array(
      '#type' => 'textfield',
      '#title' => t('Mail address of the API user'),
      '#default_value' => $config->get('zendesk_api_mail'),
      '#description' => t('This is typically the mail address of the zendesk admin account'),
    );


    // Role-based visibility settings.
    foreach (user_roles() as $machine_name => $role_object) {
      $role_options[$machine_name] = $role_object->id();
    }

    $form['zendesk']['zendesk_permissions'] = array(
      '#type' => 'fieldset',
      '#description' => t('Restrict access to zendesk based on user roles. These rules will apply for both user synchronization as remote authentication.'),
    );

    $form['zendesk']['zendesk_permissions']['zendesk_roles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Authenticate only for specific roles'),
      '#default_value' => $config->get('zendesk_roles'),
      '#options' => $role_options,
      '#description' => t('Select which roles may be authenticated for zendesk. If you select no roles, all authenticated drupal users will be authenticated for Zendesk.'),
    );

    $form['zendesk']['zendesk_permissions']['zendesk_no_permission_page'] = array(
      '#type' => 'textfield',
      '#title' => t('No permission page'),
      '#default_value' => $config->get('zendesk_no_permission_page'),
      '#description' => t('To what pages do you want to redirect user that have no permission to access Zendesk.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}c
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('zendesk.settings')
      ->set('zendesk_url', $form_state->getValue('zendesk_url'))
      ->set('zendesk_subdomain', $form_state->getValue('zendesk_subdomain'))
      ->set('zendesk_api_token', $form_state->getValue('zendesk_api_token'))
      ->set('zendesk_api_mail', $form_state->getValue('zendesk_api_mail'))
      ->set('zendesk_roles', $form_state->getValue('zendesk_roles'))
      ->set('zendesk_no_permission_page', $form_state->getValue('zendesk_no_permission_page'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}

