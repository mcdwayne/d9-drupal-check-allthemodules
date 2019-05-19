<?php

namespace Drupal\system_tags\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\EntityDeleteFormTrait;

/**
 * Class SystemTagDeleteForm.
 *
 * @package Drupal\system_tag\Form
 */
class SystemTagDeleteForm extends EntityConfirmFormBase {

  use EntityDeleteFormTrait;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the System Tag %label?', [
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl('collection');
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
  protected function getDeletionMessage() {
    return $this->t('Deleted System Tag %label.', [
      '%label' => $this->entity->label(),
    ]);
  }

}
