<?php

namespace Drupal\entity_legal_state\Form;

use Drupal\entity_legal\Form\EntityLegalDocumentVersionForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EntityLegalStateDocumentVersionForm.
 *
 * Overrides the default entity_legal form, saving published version to state
 * instead of config entity.
 *
 * @package Drupal\entity_legal_state
 */
class EntityLegalStateDocumentVersionForm extends EntityLegalDocumentVersionForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();

    // Set this as the published version.
    $document = $this->entity->getDocument();
    if (!$document->getPublishedVersion()) {
      \Drupal::service('entity_legal_state')->updateStateVersion($document, $this->entity->id());
    }

    $form_state->setRedirect('entity.entity_legal_document.edit_form', ['entity_legal_document' => $this->entity->bundle()]);
  }

}
