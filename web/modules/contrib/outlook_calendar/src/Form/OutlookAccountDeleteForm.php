<?php

namespace Drupal\outlook_calendar\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;

/**
 * Class OutlookAccountDeleteForm.
 *
 * @package Drupal\outlook_calendar\Form
 */
class OutlookAccountDeleteForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'outlook_account_delete_form';
  }

  public $cid;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to delete %cid?', [
      '%cid' => $this->cid,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('outlook_calendar.account');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('This action cannot be undone. Only do this if you are sure');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete it!');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cid = NULL) {
    $this->id = $cid;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = \Drupal::database();
    $query->delete('outlook_calendar')->condition('id', $this->id)->execute();
    drupal_set_message($this->t('The Outlook Account has been succesfully deleted'));
    $form_state->setRedirect('outlook_calendar.account');
  }

}
