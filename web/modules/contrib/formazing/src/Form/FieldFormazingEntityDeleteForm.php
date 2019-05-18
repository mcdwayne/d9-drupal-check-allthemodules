<?php

namespace Drupal\formazing\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting Field formazing entity entities.
 *
 * @ingroup formazing
 */
class FieldFormazingEntityDeleteForm extends ContentEntityDeleteForm {

  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();

    // Make sure that deleting a translation does not delete the whole entity.
    if (!$entity->isDefaultTranslation()) {
      $untranslated_entity = $entity->getUntranslated();
      $untranslated_entity->removeTranslation($entity->language()->getId());
      $untranslated_entity->save();
      $form_state->setRedirectUrl(Url::fromRoute('entity.formazing_entity_elements.view', ['formazing_entity' => $entity->getFormId()]));
    }
    else {
      $entity->delete();
      $form_state->setRedirectUrl(Url::fromRoute('entity.formazing_entity_elements.view', ['formazing_entity' => $entity->getFormId()]));
    }

    \Drupal::messenger()->addStatus($this->getDeletionMessage());
    $this->logDeletionMessage();
  }

}
