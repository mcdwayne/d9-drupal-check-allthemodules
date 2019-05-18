<?php

/**
 * @file
 * Contains \Drupal\rswebmail\Form\RswebmailCreateForm.
 */

namespace Drupal\rswebmail\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure RSWEBmail settings for this site.
 */
class RswebmailCreateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rswebmail_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	rswebmail_nusoap_library_exists();
	$record = rswebmail_details();
    $form['org_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Organization name'),
      '#default_value' => (isset($record->org_name)) ? $record->org_name : NULL,
    );

    $form['host_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Host Name'),
      '#description' => t('Host name should be in the format of YOURHOSTNAME.COM (http or www in prefix not allow)'),
      '#default_value' => (isset($record->host_name)) ? $record->host_name : NULL,
    );

    $form['rs_uname'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Reseller Username'),
      '#default_value' => (isset($record->rs_uname)) ? $record->rs_uname : NULL,
    );

    $form['rs_pass'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Reseller Password'),
      '#default_value' => (isset($record->rs_pass)) ? $record->rs_pass : NULL,
    );
	
	$form['actions'] = array('#type' => 'actions');
    $form['actions']['save'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $server_name = $form_state->getValue('host_name');
    $check_valid1 = stristr($server_name, 'http://');
    $check_valid2 = stristr($server_name, 'www');
    if ($check_valid1 == TRUE) {
      $form_state->setErrorByName('host_name', $this->t('http:// not allowed in prefix of host name.'));
    }
    if ($check_valid2 == TRUE) {
      $form_state->setErrorByName('host_name', $this->t('www not allowed in prefix of host name.'));
    }  
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $result = rswebmail_details();
    if (empty($result)) {
      db_insert('rswebmail_config')
        ->fields(array(
        'org_name' => $form_state->getValue('org_name'),
        'host_name' => $form_state->getValue('host_name'),
        'rs_uname' => $form_state->getValue('rs_uname'),
        'rs_pass' => $form_state->getValue('rs_pass'),
      ))->execute();
      drupal_set_message($this->t("Your Rackspace webmail information has been successfully saved."));
    }
    else {
      db_update('rswebmail_config')
        ->fields(array(
        'org_name' => $form_state->getValue('org_name'),
        'host_name' => $form_state->getValue('host_name'),
        'rs_uname' => $form_state->getValue('rs_uname'),
        'rs_pass' => $form_state->getValue('rs_pass'),
      ))->execute();
      drupal_set_message($this->t("Your Rackspace webmail information has been successfully updated."));
    }
  }

}
