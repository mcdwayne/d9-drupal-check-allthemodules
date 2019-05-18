<?php

namespace Drupal\mailman_integration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\mailman_integration\MailmanIntegration;

/**
 * Mailman integration admin settings form.
 */
class MailmanIntegrationAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailman_integration_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mailman_integration.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mailman_integration.settings');
    $form['mailman_integration_admin_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mailman Integration URL'),
      '#required' => TRUE,
      '#default_value' => $config->get('mailman_integration_admin_url'),
      '#description' => $this->t("Mailman Integration Url. Note: If your mailman url like as http://demo.com/mailman/admin then give the url as http://demo.com/mailman"),
    ];
    $form['mailman_integration_domain_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mailman Integration Domain Name'),
      '#required' => TRUE,
      '#default_value' => $config->get('mailman_integration_domain_name'),
      '#description' => $this->t("List Domain Name. Note: Don't include http://www. It should be like demo.com"),
    ];
    $form['mailman_integration_authenticate_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Mailman Integration Authentication Password'),
      '#required' => TRUE,
      '#default_value' => $config->get('mailman_integration_authenticate_password'),
      '#description' => $this->t("Mailman Integration Authentication Password"),
    ];
    $form['mailman_integration_sub_acknowledgement_to_user'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send Subscription acknowledgement to the user'),
      '#default_value' => $config->get('mailman_integration_sub_acknowledgement_to_user'),
      '#description' => $this->t('If checked, Send Subscription acknowledgement to the user.'),
    ];
    $form['mailman_integration_sub_acknowledgement_to_owner'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send Subscription acknowledgement to the List Owner'),
      '#default_value' => $config->get('mailman_integration_sub_acknowledgement_to_owner'),
      '#description' => $this->t('If checked, Send Subscription acknowledgement to the List Owner.'),
    ];
    $form['mailman_integration_unsub_acknowledgement_to_user'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send Unsubscription acknowledgement to the user'),
      '#default_value' => $config->get('mailman_integration_unsub_acknowledgement_to_user'),
      '#description' => $this->t('If checked, Send Unsubscription acknowledgement to the user.'),
    ];
    $form['mailman_integration_unsub_acknowledgement_to_owner'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send Unsubscription acknowledgement to the List Owner'),
      '#default_value' => $config->get('mailman_integration_unsub_acknowledgement_to_owner'),
      '#description' => $this->t('If checked, Send Unsubscription acknowledgement to the List Owner.'),
    ];
    $options = [
      10 => 10,
      20 => 20,
      30 => 30,
      40 => 40,
      50 => 50,
    ];
    $form['mailman_integration_list_pagination'] = [
      '#type' => 'select',
      '#title' => $this->t('Mailman List Per Page'),
      '#default_value' => $config->get('mailman_integration_list_pagination'),
      '#options' => $options,
    ];
    $form['mailman_integration_auto_sync'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mailman list Sync Cron'),
      '#default_value' => $config->get('mailman_integration_auto_sync'),
      '#description' => $this->t('If checked, When the Cron is run automatically sync the mailman list.'),
    ];
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $authenticate_pass = $form_state->getValue(['mailman_integration_authenticate_password']);
    $admin_url = $form_state->getValue(['mailman_integration_admin_url']);
    $site_mail = \Drupal::config('system.site')->get('mail');
    $config = $this->config('mailman_integration.settings');
    $mailman = MailmanIntegration::getInstance($admin_url, '', '', '', 1);
    $lists = $mailman->getMailmanLists();
    $list_name = isset($lists[0]['name']) ? $lists[0]['name'] : '';
    if (!$list_name) {
      $list_mail_address = ($site_mail) ? $site_mail : 'test-connection@' . $form_state->getValue(['mailman_integration_domain_name']);
      $list_name = 'test-connection';
      $mailman = MailmanIntegration::getInstance($admin_url, $authenticate_pass, $authenticate_pass, $list_name, 1);
      $params = [];
      $params['autogen'] = 0;
      $params['doit'] = 'Create List';
      $params['langs'] = 'en';
      $params['listname'] = $list_name;
      $params['moderate'] = 0;
      $params['notify'] = 0;
      $params['owner'] = $list_mail_address;
      $mailman->mailmanListCreate($params);
    }
    $config->set('mailman_domain_url', 0);
    $mailman = MailmanIntegration::getInstance($admin_url, '', $authenticate_pass, $list_name, 1);
    $list_val = $mailman->getMailmanListGeneral();
    if (!isset($list_val['real_name']) || strtolower($list_val['real_name']) != strtolower($list_name)) {
      $list_name_with_domain = $list_name . '_' . $form_state->getValue(['mailman_integration_domain_name']);
      $list_val = $mailman->getMailmanListGeneral($list_name_with_domain);
      if (!isset($list_val['real_name']) || strtolower($list_val['real_name']) != strtolower($list_name)) {
        $form_state->setErrorByName('mailman_integration_authenticate_password', $this->t('Mailman Authentication Failed.'));
      }
      else {
        $config->set('mailman_domain_url', 1);
      }
    }
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('mailman_integration.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->set('mailman_connection_error', 0);
    $config->save();
  }

}
