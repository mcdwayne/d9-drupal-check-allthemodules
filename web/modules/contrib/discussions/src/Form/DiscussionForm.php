<?php

namespace Drupal\discussions\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Discussion entity edit form.
 *
 * @ingroup discussions
 */
class DiscussionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $entity->set('uid', \Drupal::currentUser()->id());

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created %label.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved %label.', [
          '%label' => $entity->label(),
        ]));
    }

    $form_state->setRedirect('entity.discussion.canonical', [
      'discussion' => $entity->id(),
    ]);
  }

}
