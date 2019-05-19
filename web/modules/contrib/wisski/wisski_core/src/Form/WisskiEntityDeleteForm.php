<?php

namespace Drupal\wisski_core\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use \Drupal\Core\Form\FormStateInterface;

class WisskiEntityDeleteForm extends ContentEntityDeleteForm {

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form,$form_state);
    $form['#title'] = $this->t('Delete').' '.$this->entity->label();
    return $form;
  }
}