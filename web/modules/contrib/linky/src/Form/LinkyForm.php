<?php

namespace Drupal\linky\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Linky edit forms.
 *
 * @ingroup linky
 */
class LinkyForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Managed link.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Managed Link.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.linky.canonical', ['linky' => $entity->id()]);
  }

}
