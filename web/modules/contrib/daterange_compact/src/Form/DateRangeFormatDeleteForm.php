<?php

namespace Drupal\daterange_compact\Form;

use Drupal\Core\Entity\EntityDeleteForm;

/**
 * Provides a form for deleting a date range format.
 *
 * @package Drupal\daterange_compact\Form
 */
class DateRangeFormatDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the date range format %name?', ['%name' => $this->entity->label()]);
  }

}
