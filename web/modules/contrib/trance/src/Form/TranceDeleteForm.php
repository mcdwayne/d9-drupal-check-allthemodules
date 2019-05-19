<?php

namespace Drupal\trance\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * Provides a form for deleting trance entities.
 *
 * @ingroup trance
 */
class TranceDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    /** @var \Drupal\trance\TranceInterface $entity */
    $entity = $this->getEntity();

    if (!$entity->isDefaultTranslation()) {
      return $this->t('@language translation of the @type @bundle %label has been deleted.', [
        '@language' => $entity->language()->getName(),
        '@type' => $entity->getEntityType()->id(),
        '@bundle' => $entity->getType(),
        '%label' => $entity->label(),
      ]);
    }

    return $this->t('The @type @bundle %title has been deleted.', [
      '@type' => $entity->getEntityType()->id(),
      '@bundle' => $entity->getType(),
      '%title' => $entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function logDeletionMessage() {
    /** @var \Drupal\trance\TranceInterface $entity */
    $entity = $this->getEntity();
    $this->logger('content')->notice('@type: deleted %title.', [
      '@type' => $entity->getType(),
      '%title' => $entity->label(),
    ]);
  }

}
