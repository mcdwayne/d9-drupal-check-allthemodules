<?php

namespace Drupal\mailman_integration\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Mail list delete confirm form.
 */
class MailmanIntegrationListDeleteConfirm extends ConfirmFormBase {

  /**
   * The ID of the item to delete.
   *
   * @var string
   */
  protected $listTitle;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailman_integration_list_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %title?', array('%title' => $this->listTitle));
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
  public function getCancelUrl() {
    return new Url('mailman_integration.view_list');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $list_name = NULL) {
    $this->listTitle = $list_name;
    $form['list_name'] = ['#type' => 'value', '#value' => $list_name];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue(['confirm'])) {
      $list_name = $form_state->getValue(['list_name']);
      mailman_integration_remove_list($list_name);
      drupal_set_message(t('%title has been deleted.', ['%title' => $list_name]));
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
