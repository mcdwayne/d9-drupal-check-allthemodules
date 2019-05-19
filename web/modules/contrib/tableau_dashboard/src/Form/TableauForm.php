<?php

namespace Drupal\tableau_dashboard\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings Form of the module.
 *
 * @package Drupal\tableau_dashboard\Form
 */
class TableauForm extends ConfigFormBase {

  /**
   * Name of the form.
   *
   * @return string
   *   Form ID.
   */
  public function getFormId() {
    return 'tableau_dashboard';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('tableau_dashboard.settings');

    // Detail elements
    $form['api_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API settings'),
      '#open' => TRUE,
    ];
    $form['javascript_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Javascript options'),
    ];
    $form['device_dimensions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Device settings'),
    ];

    // Form Fields
    $form['api_settings']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL:'),
      '#default_value' => $config->get('url'),
      '#description' => $this->t('URL of the Tableau Server. For example https://127.0.0.1.'),
      '#required' => TRUE,
    ];
    $form['api_settings']['admin_user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username:'),
      '#default_value' => $config->get('admin_user'),
      '#description' => $this->t('Username of the user which has Admin access in Tableau. Used to make API calls.'),
      '#required' => TRUE,
    ];
    $form['api_settings']['admin_user_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password:'),
      '#default_value' => $config->get('admin_user_password'),
      '#description' => $this->t('Password of the user which has Admin access in Tableau. Used to make API calls.'),
    ];
    $form['api_settings']['api_version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Version:'),
      '#default_value' => $config->get('api_version') ? $config->get('api_version') : '2.5',
      '#description' => $this->t('Version of API which is going to be used to make API Calls to Tableau.'),
      '#required' => TRUE,
    ];
    $form['api_settings']['site_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site Name:'),
      '#default_value' => $config->get('site_name'),
      '#description' => $this->t('Name of the site given when it was created. If you are using the Default site then leave this empty.'),
    ];
    $form['api_settings']['user_role'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User Role:'),
      '#default_value' => $config->get('user_role'),
      '#description' => $this->t('User role in to which users should be enrolled upon creation. Current available Tableau user roles are: Interactor, Publisher, SiteAdministrator, Unlicensed, UnlicensedWithPublish, Viewer, or ViewerWithPublish.'),
      '#required' => TRUE,
    ];
    $form['api_settings']['group_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Group ID:'),
      '#default_value' => $config->get('group_id'),
      '#description' => $this->t('In which group should user be assigned to upon creation? Value needs to be Group ID. List of Groups can be seen by making "Query Groups" API call.'),
    ];
    $form['javascript_options']['show_tabs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Tabs:'),
      '#default_value' => $config->get('show_tabs'),
      '#description' => $this->t('Show or hide tabs in rendered dashboard.'),
    ];
    $form['javascript_options']['show_toolbar'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Toolbar:'),
      '#default_value' => $config->get('show_toolbar'),
      '#description' => $this->t('Show or hide toolbar in rendered dashboard.'),
    ];
    $form['device_dimensions']['desktop_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Desktop Width:'),
      '#default_value' => $config->get('desktop_width'),
      '#description' => $this->t('The pixel width of desktop views.'),
    ];
    $form['device_dimensions']['tablet_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Tablet Width:'),
      '#default_value' => $config->get('tablet_width'),
      '#description' => $this->t('The pixel width of tablet views.'),
    ];
    $form['device_dimensions']['mobile_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Mobile Width:'),
      '#default_value' => $config->get('mobile_width'),
      '#description' => $this->t('The pixel width of mobile views.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('tableau_dashboard.settings');
    $config->set('url', $form_state->getValue('url'));
    $config->set('admin_user', $form_state->getValue('admin_user'));
    if ($password = $form_state->getValue('admin_user_password')) {
      $config->set('admin_user_password', $password);
    }
    $config->set('api_version', $form_state->getValue('api_version'));
    $config->set('site_name', $form_state->getValue('site_name'));
    $config->set('user_role', $form_state->getValue('user_role'));
    $config->set('group_id', $form_state->getValue('group_id'));
    $config->set('show_tabs', $form_state->getValue('show_tabs'));
    $config->set('show_toolbar', $form_state->getValue('show_toolbar'));
    $config->set('show_tabs', $form_state->getValue('show_tabs'));
    $config->set('desktop_width', $form_state->getValue('desktop_width'));
    $config->set('tablet_width', $form_state->getValue('tablet_width'));
    $config->set('mobile_width', $form_state->getValue('mobile_width'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'tableau_dashboard.settings',
    ];
  }
}
