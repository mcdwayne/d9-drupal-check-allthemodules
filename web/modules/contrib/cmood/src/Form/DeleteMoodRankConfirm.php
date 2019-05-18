<?php

namespace Drupal\cmood\Form;

use Drupal\cmood\Storage\CmoodStorage;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a confirmation form for deleting cmood data.
 */
class DeleteMoodRankConfirm extends ConfirmFormBase {

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
    return 'delete-rank-word';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you actually want to delete?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('cmood.admin_cmood_rank');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('This action can not be undone!');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete it Now!');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param int $id
   *   (optional) The ID of the item to be deleted.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $this->id = $id;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    CmoodStorage::deleteCmoodRecords('rank_word_with_weight', [
      'field' => 'rwid',
      'value' => $this->id,
    ]);
    $form_state->setRedirect('cmood.admin_cmood_rank');
  }

}
