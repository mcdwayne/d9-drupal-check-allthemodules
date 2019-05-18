<?php

/**
 * @file
 * Contains Drupal\acquia_cloud_dashboard\Form\SSHKeyForm.
 */

namespace Drupal\acquia_cloud_dashboard\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\acquia_cloud_dashboard\CloudAPICommand;

class SSHKeyForm extends ConfigFormBase {

  protected $site;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'acquia_cloud_dashboard_ssh_key';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $site_name = NULL) {
    $this->site = $site_name;

    $form['site'] = array(
      '#type' => 'textfield',
      '#title' => t('Acquia Cloud Site'),
      '#default_value' => $this->site,
      '#size' => '40',
      '#required' => TRUE,
      '#disabled' => TRUE,
    );
    $form['nickname'] = array(
      '#type' => 'textfield',
      '#title' => t('Nickname for the Key'),
      '#size' => '40',
      '#required' => TRUE,
    );
    $form['key'] = array(
      '#type' => 'textarea',
      '#title' => t('Key to be added'),
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $api = new CloudAPICommand();
    $api->postMethod('sites/' . $this->site . '/sshkeys', 'POST', TRUE, array(
      'ssh_pub_key' => $form_state['values']['key'],
    ), array(
      'nickname' => $form_state['values']['nickname'],
    ));
    $api->refreshKeys($this->site);
    $form_state['redirect'] = 'admin/config/cloud-api/view';
    parent::submitForm($form, $form_state);
  }

}