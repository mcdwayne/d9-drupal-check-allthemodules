<?php

namespace Drupal\ckeditor_content_style\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;

/**
 * Class DeleteForm.
 *
 * @package Drupal\ckeditor_content_style\Form
 */
class DeleteForm extends ConfirmFormBase {
  public $cid;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to delete %cid?', ['%cid' => $this->cid]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('ckcs.ckcs_display');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Only do this if you are sure!');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete it!');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
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
    $query->delete('contentstyle')
      ->condition('id', $this->id)
      ->execute();
    drupal_set_message($this->t("succesfully deleted"));
    $form_state->setRedirect('ckcs.ckcs_display');
  }

}
