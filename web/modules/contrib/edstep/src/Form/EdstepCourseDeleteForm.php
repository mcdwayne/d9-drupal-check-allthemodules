<?php

namespace Drupal\edstep\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EdstepCourseDeleteForm.
 */
class EdstepCourseDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('The course will only be removed from this site, not from EdStep. You can add it again later.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t('The course has been removed from this site.');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to remove the course %label from this site?', [
      '%label' => $this->getEntity()->label(),
    ]);
  }
}
