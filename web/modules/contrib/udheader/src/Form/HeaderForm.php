<?php

namespace Drupal\udheader\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the udheader entity edit forms.
 *
 * @ingroup udheader
 */
class HeaderForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->setNewRevision();

    $status = parent::save($form, $form_state);

    if ($status == SAVED_UPDATED) {
      \Drupal::messenger()->addMessage(
        $this->t(
          'The header %feed has been updated.',
          ['%feed' => $entity->toLink()->toString()]
        )
      );
    } else {
      \Drupal::messenger()->addMessage(
        $this->t(
          'The header %feed has been added.',
          ['%feed' => $entity->toLink()->toString()]
        )
      );
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $status;
  }
}
