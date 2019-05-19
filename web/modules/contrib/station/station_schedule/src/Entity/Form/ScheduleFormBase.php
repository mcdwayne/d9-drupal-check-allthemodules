<?php

/**
 * @file
 * Contains \Drupal\station_schedule\Entity\Form\ScheduleFormBase.
 */

namespace Drupal\station_schedule\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * @todo.
 */
class ScheduleFormBase extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);
    if ($status) {
      $form_state->setRedirectUrl($this->getEntity()->toUrl());
    }
  }

}
