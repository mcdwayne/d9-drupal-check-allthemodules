<?php

namespace Drupal\pfdp\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Directory delete form for private_files_download_permission.
 */
class DirectoryDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $pfdp_directory = $this->entity;
    //
    return $this->t('Are you sure you want to delete the directory %path from the control list?', ['%path' => $pfdp_directory->path]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.pfdp_directory');
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $pfdp_directory = $this->entity;
    // Delete the directory and display the status message.
    try {
      $pfdp_directory->delete();
      $this->logger('pfdp')->info('The directory %path was deleted successfully.', ['%path' => $pfdp_directory->path]);
      drupal_set_message($this->t('The directory %path was deleted successfully.', ['%path' => $pfdp_directory->path]), 'status');
    }
    catch (EntityStorageException $exception) {
      $this->logger('pfdp')->error('The directory %path was not deleted.', ['%path' => $pfdp_directory->path]);
      drupal_set_message($this->t('The directory %path was not deleted.', ['%path' => $pfdp_directory->path]), 'error');
    }
    // Set form redirection.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
