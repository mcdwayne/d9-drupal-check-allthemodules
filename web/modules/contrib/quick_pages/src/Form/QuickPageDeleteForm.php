<?php

/**
 * @file
 * Contains Drupal\quick_pages\Form\QuickPageDeleteForm.
 */

namespace Drupal\quick_pages\Form;

use Drupal\Core\Entity\EntityDeleteForm;

/**
 * Builds the form to delete a quick page.
 */
class QuickPageDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '_confirm_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the quick page %title?', ['%title' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t('Deleted quick page  %name.', ['%name' => $this->entity->label()]);
  }

}
