<?php

namespace Drupal\content_type_dependency\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Defines a confirmation form for deleting content_type_dependency data.
 */

class ContentTypeDependencyDeleteConfirm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_type_dependency_delete_confirm';
  }

  /**
   * The ID of the item to delete.
   *
   * @var string
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to delete %id?', array('%id' => $this->id));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('content_type_dependency.list');
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
   *
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
    drupal_set_message(t('Rule deleted successfully!'));
    \Drupal::database()->delete('content_type_dependency')
      ->condition('cd_id', $this->id )
      ->execute();
    $form_state->setRedirect('content_type_dependency.list');
  }
}