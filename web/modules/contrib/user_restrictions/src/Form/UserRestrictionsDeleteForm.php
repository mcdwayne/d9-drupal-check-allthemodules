<?php

namespace Drupal\user_restrictions\Form;

use Drupal\Core\Entity\EntityDeleteForm;

/**
 * Creates a form to delete a user restriction rule.
 */
class UserRestrictionsDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the user restriction rule %label?', ['%label' => $this->entity->label()]);
  }

}
