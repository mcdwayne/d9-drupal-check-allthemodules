<?php

namespace Drupal\libraries_provider_ui\Form;

use Drupal\Core\Entity\EntityDeleteForm;

/**
 * Defines a confirmation form for deletion of a custom library.
 *
 * @internal
 */
class LibraryDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this
      ->t('Are you sure you want to revert to the default the @entity-type %label?', [
        '@entity-type' => $this
          ->getEntity()
          ->getEntityType()
          ->getLowercaseLabel(),
        '%label' => $this
          ->getEntity()
          ->label(),
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this
      ->t('Revert');
  }

  /**
   * {@inheritdoc}
   */
  protected function logDeletionMessage() {
    $this->logger('libraries_provider_ui')->notice('Library "%title" reverterd to defaults.', ['%title' => $this->entity->label()]);
  }

}
