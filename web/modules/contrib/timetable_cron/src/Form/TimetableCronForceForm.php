<?php

namespace Drupal\timetable_cron\Form;

use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to force an cron.
 */
class TimetableCronForceForm extends EntityConfirmFormBase {

  // The Messenger service.
  use MessengerTrait;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want force %name?', ['%name' => $this->entity->id()]);
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
    return $this->t('Force');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->force = TRUE;
    $this->entity->save();
    $this->messenger()->addMessage($this->t('Cron %name has been force.', ['%name' => $this->entity->id()]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
