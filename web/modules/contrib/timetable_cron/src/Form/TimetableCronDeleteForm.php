<?php

namespace Drupal\timetable_cron\Form;

use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to delete an cron.
 */
class TimetableCronDeleteForm extends EntityConfirmFormBase {

  // The Messenger service.
  use MessengerTrait;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.timetable_cron.collection');
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
    $this->entity->delete();
    $this->messenger()->addMessage($this->t('Cron %name has been deleted.', ['%name' => $this->entity->id()]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
