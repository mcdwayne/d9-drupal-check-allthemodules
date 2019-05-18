<?php

namespace Drupal\blocktabs\Form;

use Drupal\Core\Entity\EntityDeleteForm;

/**
 * Creates a form to delete blocktabs.
 */
class BlocktabsDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Optionally select a blocktabs before deleting %blocktabs', ['%blocktabs' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('If this block tabs is in use on the site, this block tabs will be permanently deleted.');
  }

}
