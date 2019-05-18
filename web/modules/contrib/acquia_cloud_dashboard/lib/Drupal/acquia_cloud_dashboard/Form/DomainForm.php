<?php

/**
 * @file
 * Contains Drupal\acquia_cloud_dashboard\Form\DomainForm.
 */

namespace Drupal\acquia_cloud_dashboard\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\acquia_cloud_dashboard\CloudAPICommand;

class DomainForm extends ConfigFormBase {

  protected $site;
  protected $environment;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'acquia_cloud_dashboard_domain';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $site_name = NULL, $env = NULL) {
    $report = \Drupal::config('acquia_cloud_dashboard.settings')->get('report');
    $this->site = $site_name;
    $this->environment = $env;

    $sites_option = array();
    $environment_option = array();

    foreach ($report['sites'] as $site) {
      $sites_option[$site['name']] = $site['name'];
      foreach ($site['environments'] as $environment) {
        $environment_option[$site['name']][$environment['name']] = $environment['name'];
      }
    }

    $form['site'] = array(
      '#type' => 'select',
      '#title' => t('Acquia Cloud Site'),
      '#default_value' => $this->site,
      '#options' => $sites_option,
      '#required' => TRUE,
    );
    foreach ($sites_option as $site) {
      $form['environment_' . $site] = array(
        '#type' => 'select',
        '#title' => t('Acquia Cloud Environment'),
        '#options' => $environment_option[$site],
        '#states' => array(
          'visible' => array(
            ':input[name="site"]' => array('value' => $site),
          ),
          'required' => array(
            ':input[name="site"]' => array('value' => $site),
          ),
        ),
      );
      if ($site = $this->site) {
        $form['environment'][$site]['#default_value'] = $this->environment;
      }
    }
    $form['domain'] = array(
      '#type' => 'textfield',
      '#title' => t('Domain Name to be added'),
      '#size' => '40',
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->site = $form_state['values']['site'];
    $this->environment = $form_state['values']['environment_' . $this->site];
    $domain = $form_state['values']['domain'];

    $api = new CloudAPICommand();
    $api->postMethod('sites/' . $this->site . '/envs/' . $this->environment . '/domains/' . $domain);
    $api->refreshDomains($this->site, $this->environment);
    $form_state['redirect'] = 'admin/config/cloud-api/view';

    parent::submitForm($form, $form_state);
  }

}