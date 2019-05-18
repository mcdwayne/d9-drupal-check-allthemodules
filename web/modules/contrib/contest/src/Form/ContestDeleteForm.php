<?php

namespace Drupal\contest\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Contest delete form.
 */
class ContestDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * Get the cancel URL object.
   *
   * @return Drupal\Core\Url
   *   A Url object.
   */
  public function getCancelUrl() {
    return new Url('contest.contest_list');
  }

  /**
   * Get the confirmation label.
   *
   * @return string
   *   The confirmation label.
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * Get the confirmation description.
   *
   * @return string
   *   The confirmation description.
   */
  public function getDescription() {
    return $this->t('All associated entries will be deleted too. This action cannot be undone.');
  }

  /**
   * Get the confirmation question.
   *
   * @return string
   *   The confirmation question.
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this contest: %contest?', ['%contest' => $this->entity->label()]);
  }

  /**
   * Delete the contest entity and redirect.
   *
   * @param array $form
   *   A drupal form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal form state object.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    \Drupal::logger('contest')->notice('Contest %contest deleted.', ['%contest' => $this->entity->label()]);

    drupal_set_message($this->t('The contest %contest has been deleted.', ['%contest' => $this->entity->label()]));

    $form_state->setRedirect('contest.contest_list');
  }

}
