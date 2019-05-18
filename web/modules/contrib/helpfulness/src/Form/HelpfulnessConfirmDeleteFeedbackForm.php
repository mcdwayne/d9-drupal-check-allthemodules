<?php

namespace Drupal\helpfulness\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;

/**
 * Builds the form to confirm the deletion of a feedback submission.
 */
class HelpfulnessConfirmDeleteFeedbackForm extends ConfirmFormBase {

  /**
   * The array of IDs of the items to delete.
   *
   * @var array
   */
  protected $ids;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'helpfulness_confirm_delete_feedback_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to delete the feedbacks?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('helpfulness.report_form');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    if (count($this->ids) > 1) {
      return t('Are you sure that you want to delete the selected %count feedback items?', ['%count' => count($this->ids)]);
    }
    return t('Are you sure that you want to delete the selected feedback item?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Yes, delete!');
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
  public function buildForm(array $form, FormStateInterface $form_state, $idstring = NULL) {
    $this->ids = explode('-', $idstring);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!empty($this->ids)) {
      // Build the update query and execute.
      $db = Database::getConnection();
      $query = $db->update('helpfulness');
      $query->fields(['status' => HELPFULNESS_STATUS_DELETED]);
      $query->condition('fid', $this->ids, 'IN');
      $query->execute();
    }

    drupal_set_message(t('The selected feedbacks have been deleted.'));
    $form_state->setRedirect('helpfulness.report_form');
  }

}
