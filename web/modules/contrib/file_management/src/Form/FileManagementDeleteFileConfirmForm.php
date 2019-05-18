<?php

namespace Drupal\file_management\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a confirmation form for deleting mymodule data.
 */
class FileManagementDeleteFileConfirmForm extends ConfirmFormBase {

  /**
   * The file to be deleted.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $file;

  /**
   * The ID of the item to delete.
   *
   * @var String
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'file_management_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to delete the file "%label" ?', ['%label' => $this->file->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('file_management_view.overview');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('This could break some pages and media entities if they use this file.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Yes.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return t('No, go back.');
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\file\FileInterface $file
   *   (optional) The file to be deleted.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $file = NULL) {
    $this->file = $file;
    $this->id = $file->id();
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (file_exists($this->file->getFileUri())) {
      $this->file->delete();
      \Drupal::messenger()->addMessage(t('File "%label" has been deleted.', [
        '%label' => $this->file->label(),
      ]), 'status');
    }
    else {
      \Drupal::messenger()->addMessage(t('File "%label" could not be deleted.', [
        '%label' => $this->file->label(),
      ]), 'error');
    }
  }

}
