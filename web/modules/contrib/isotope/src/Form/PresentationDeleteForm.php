<?php

/**
 * @file
 * Contains Drupal\isotope\Form\PresentationDeleteForm.
 */

namespace Drupal\isotope\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PresentationDeleteForm.
 *
 * Provides a confirm form for deleting the entity. This is different from the
 * add and edit forms as it does not inherit from PresentationFormBase. The
 * reason for this is that we do not need to build the same form. Instead, we
 * present the user with a simple yes/no question. For this reason, we derive
 * from EntityConfirmFormBase instead.
 *
 * @package Drupal\isotope\Form
 *
 * @ingroup isotope
 */
class PresentationDeleteForm extends EntityConfirmFormBase {

  /**
   * Gathers a confirmation question.
   *
   * @return string
   *   Translated string.
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete Presentation %label?', [
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * Gather the confirmation text.
   *
   * The confirm text is used as the text in the button that confirms the
   * question posed by getQuestion().
   *
   * @return string
   *   Translated string.
   */
  public function getConfirmText() {
    return $this->t('Delete Presentation');
  }

  /**
   * Gets the cancel URL.
   *
   * Provides the URL to go to if the user cancels the action.
   *
   * @return \Drupal\Core\Url
   *   The URL to go to if the user cancels the deletion.
   */
  public function getCancelUrl() {
    return new Url('entity.presentation.list');
  }

  /**
   * The submit handler for the confirm form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Delete the entity.
    $this->entity->delete();

    // Set a message that the entity was deleted.
    drupal_set_message($this->t('Presentation %label was deleted.', [
      '%label' => $this->entity->label(),
    ]));

    // Redirect the user to the list controller when complete.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
