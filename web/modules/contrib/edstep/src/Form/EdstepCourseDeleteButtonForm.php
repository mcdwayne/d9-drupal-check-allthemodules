<?php

namespace Drupal\edstep\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EdstepCourseDeleteButtonForm.
 */
class EdstepCourseDeleteButtonForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Remove');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $edstep_course = $this->getEntity();
    if($edstep_course->isNew()) {
      return [];
    }
    unset($form['actions']['#type']);
    unset($form['#theme']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $edstep_course = $this->getEntity();
    $edstep_course->delete();
    drupal_set_message($this->t('The EdStep course %title has been removed from this site.', [
      '%title' => $edstep_course->label(),
    ]));
  }

}
