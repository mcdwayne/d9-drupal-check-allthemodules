<?php

namespace Drupal\abjs\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class for confirm delete condition.
 */
class AbjsConditionDeleteConfirmForm extends ConfirmFormBase {
  /**
   * The ID of the item to delete.
   *
   * @var string
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'abjs_condition_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to delete condition %id?', ['%id' => $this->id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('abjs.condition_admin');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return t('Cancel');
  }

  /**
   * Build form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Provides an object containing the current state of a form.
   * @param int $cid
   *   The ID of the item to be deleted.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cid = NULL) {
    $this->id = $cid;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $database = \Drupal::database();
    $database->delete('abjs_condition')
      ->condition('cid', $this->id)
      ->execute();
    $database->delete('abjs_test_condition')
      ->condition('cid', $this->id)
      ->execute();

    $this->messenger()->addStatus(t('Condition %id has been deleted.', ['%id' => $this->id]));

    $form_state->setRedirect('abjs.condition_admin');
  }

}
