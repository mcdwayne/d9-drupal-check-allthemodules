<?php

/**
 * @file
 * Contains \Drupal\acquia_cloud_dashboard\Form\DomainDeleteForm.
 */

namespace Drupal\acquia_cloud_dashboard\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\acquia_cloud_dashboard\CloudAPICommand;

/**
 * Form for deleting an image effect.
 */
class DomainDeleteForm extends ConfirmFormBase {

  protected $site;
  protected $environment;
  protected $domain;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the domain @name?', array('@name' => $this->domain));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
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
    return 'acquia_cloud_domain_delete_form';
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
    $api = new CloudAPICommand();
    $api->postMethod('sites/' . $this->site . '/envs/' . $this->environment . '/domains/' . $this->domain, 'DELETE');
    $api->refreshDomains($this->site, $this->environment);
    drupal_set_message($this->t('The domain name %name has been deleted.', array('%name' => $this->domain)));
    $form_state['redirect'] = 'admin/config/cloud-api/view';
  }

}
