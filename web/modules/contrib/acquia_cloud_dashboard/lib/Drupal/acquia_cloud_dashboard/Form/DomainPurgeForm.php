<?php

/**
 * @file
 * Contains \Drupal\acquia_cloud_dashboard\Form\DomainPurgeForm.
 */

namespace Drupal\acquia_cloud_dashboard\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\acquia_cloud_dashboard\CloudAPIHelper;

/**
 * Form for deleting an image effect.
 */
class DomainPurgeForm extends ConfirmFormBase {

  protected $site;
  protected $environment;
  protected $domain;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to purge Varnish on the domain @name?', array('@name' => $this->domain));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('This will purge Varnish completely across the domain.  Proceed with caution if this is a Production site as doing this will increase the load on the server while the cached pages are rebuilt.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Purge');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'acquia_cloud_dashboard.report',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_cloud_domain_purge_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $site_name = NULL, $env = NULL, $domain = NULL) {
    $this->site = $site_name;
    $this->environment = $env;
    $this->domain = $domain;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $api = new CloudAPIHelper();
    $api->postMethod('sites/' . $this->site . '/envs/' . $this->environment . '/domains/' . $this->domain . '/cache', 'DELETE');
    drupal_set_message($this->t('Varnish cache has been purged on the domain name %name.', array('%name' => $this->domain)));
    $form_state['redirect'] = 'admin/config/cloud-api/view';
  }

}
