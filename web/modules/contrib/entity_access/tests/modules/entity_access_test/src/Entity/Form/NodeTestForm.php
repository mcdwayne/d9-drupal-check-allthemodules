<?php

namespace Drupal\entity_access_test\Entity\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class NodeTestForm.
 */
class NodeTestForm extends EntityForm {

  use TestForm;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    return $this->elements($form, $form_state, $this->getEntity()->getEntityType()->getBundleEntityType());
  }

}
