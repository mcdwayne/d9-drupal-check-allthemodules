<?php

/**
 * @file
 * Contains \Drupal\station_schedule\Entity\Form\ScheduleItemDeleteForm.
 */

namespace Drupal\station_schedule\Entity\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * @todo.
 */
class ScheduleItemDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getEntity()->toUrl('edit-form');
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getEntity()->getSchedule()->toUrl('schedule');
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\station_schedule\ScheduleItemInterface
   */
  public function getEntity() {
    return parent::getEntity();
  }

}
