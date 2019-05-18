<?php

namespace Drupal\record\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the record edit forms.
 */
class RecordForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('record.admin');
    $entity = $this->getEntity();
    $entity->save();
  }

}
