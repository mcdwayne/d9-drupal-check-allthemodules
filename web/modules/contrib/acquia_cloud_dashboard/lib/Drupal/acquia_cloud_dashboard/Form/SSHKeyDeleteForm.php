<?php

/**
 * @file
 * Contains \Drupal\acquia_cloud_dashboard\Form\SSHKeyDeleteForm.
 */

namespace Drupal\acquia_cloud_dashboard\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\acquia_cloud_dashboard\CloudAPICommand;

/**
 * Form for deleting an image effect.
 */
class SSHKeyDeleteForm extends ConfirmFormBase {

  protected $siteName;
  protected $keyID;
  protected $keyNickname;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the SSH key @name?', array('@name' => $this->keyNickname));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('This will remove all access for this user to the site @site.', array('@site' => $this->siteName));
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
    return 'acquia_cloud_ssh_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $site_name = NULL, $id = NULL, $nick = NULL) {
    $this->siteName = $site_name;
    $this->keyID = $id;
    $this->keyNickname = $nick;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $api = new CloudAPICommand();
    $api->postMethod('sites/' . $this->siteName . '/sshkeys/' . $this->keyID, 'DELETE');
    $api->refreshKeys($this->siteName);
    drupal_set_message($this->t('The SSH key %name has been deleted.', array('%name' => $this->keyNickname)));
    $form_state['redirect'] = 'admin/config/cloud-api/view';
  }

}
