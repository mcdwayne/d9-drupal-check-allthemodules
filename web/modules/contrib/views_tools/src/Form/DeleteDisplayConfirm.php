<?php

namespace Drupal\views_tools\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Entity\View;

/**
 * Defines a confirmation form for deleting view display.
 */
class DeleteDisplayConfirm extends ConfirmFormBase {

  /**
   * The ID of the display item to delete.
   *
   * @var string
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_tools_display_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    // The question to display to the user.
    return t('Do you want to delete display %id?', array('%id' => $this->title));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // This needs to be a valid route otherwise the cancel link won't appear.
    return new Url('views_tools.view', ['view' => $this->id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    // A brief desccription.
    return t('This action cannot be undone!');
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
   * @param int $display_id
   *   (optional) The ID of the item to be deleted.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $view = NULL, $display_id = NULL) {
    $viewEntity = View::load($view);
    $this->id = $view;
    $this->title = $viewEntity->label();
    $this->display_id = $display_id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('views_tools.display_delete', ['view' => $this->id, 'display_id' => $this->display_id]);
  }

}
