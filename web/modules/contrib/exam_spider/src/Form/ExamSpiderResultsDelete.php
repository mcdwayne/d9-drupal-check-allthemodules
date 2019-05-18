<?php

namespace Drupal\exam_spider\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;

/**
 * Class ExamSpiderResultsDelete.
 */
class ExamSpiderResultsDelete extends ConfirmFormBase {

  /**
   * The result ID.
   *
   * @var int
   */
  public $resultid;

  /**
   * Delete Result form.
   */
  public function getFormId() {
    return 'delete_result_form';
  }

  /**
   * Delete Result confirm text.
   */
  public function getQuestion() {
    $resultid = $this->id;
    return $this->t('Do you want to delete REG - @resultid result?', ['@resultid' => $resultid]);
  }

  /**
   * Delete Result cancel url.
   */
  public function getCancelUrl() {
    return new Url('exam_spider.exam_spider_exam_results');
  }

  /**
   * Delete Result Description text.
   */
  public function getDescription() {
    return $this->t('This action cannot be undone.');
  }

  /**
   * Delete button text.
   */
  public function getConfirmText() {
    return $this->t('Delete it!');
  }

  /**
   * Cancel button text.
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * Delete Result form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $resultid = NULL) {
    $this->id = $resultid;
    return parent::buildForm($form, $form_state);
  }

  /**
   * Delete Result form submit callbacks.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $resultid = $this->id;
    db_delete('exam_results')
      ->condition('id', $resultid)
      ->execute();
    drupal_set_message($this->t('REG - @resultid result has been deleted successfully.', ['@resultid' => $resultid]));
    $form_state->setRedirect('exam_spider.exam_spider_exam_results');
  }

}
