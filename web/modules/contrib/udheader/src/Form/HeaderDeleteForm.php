<?php

namespace Drupal\udheader\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting an Ubuntu Drupal Header entity.
 *
 * @ingroup content_entity_example
 */
class HeaderDeleteForm extends ContentEntityConfirmFormBase {
  /**
   * Returns the question to ask the user.
   *
   * @return string
   *   The form question. The page title will be set to this value.
   */
  public function getQuestion() {
    return t('Are you sure you want to delete this header?');
  }

  /**
   * Returns the route to go to if the user cancels the action.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public function getCancelUrl() {
    // TODO: Implement getCancelUrl() method.
  }

  /**
   * Deletes the header.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $this->entity->delete();
      \Drupal::messenger()->addMessage($this->t('Successfully deleted the header.'));
    } catch (EntityStorageException $e) {
      \Drupal::messenger()->addMessage($this->t('Encountered an error while deleting the header.'));
    }

  }

}
