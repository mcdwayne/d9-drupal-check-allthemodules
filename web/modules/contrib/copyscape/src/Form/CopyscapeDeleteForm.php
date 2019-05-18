<?php

namespace Drupal\copyscape\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a form for deleting Copyscape entities.
 *
 * @ingroup copyscape
 */
class CopyscapeDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();

    // Make sure that deleting a translation does not delete the whole entity.
    if (!$entity->isDefaultTranslation()) {
      $untranslated_entity = $entity->getUntranslated();
      $untranslated_entity->removeTranslation($entity->language()->getId());
      $untranslated_entity->save();
      $form_state->setRedirectUrl(Url::fromRoute('copyscape.results'));
    }
    else {
      $entity->delete();
      $form_state->setRedirectUrl(Url::fromRoute('copyscape.results'));
    }

    drupal_set_message($this->getDeletionMessage());
    $this->logDeletionMessage();
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('copyscape.results');
  }

}
