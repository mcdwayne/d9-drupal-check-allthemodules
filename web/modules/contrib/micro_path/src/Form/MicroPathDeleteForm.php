<?php

namespace Drupal\micro_path\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a micro_path entity.
 *
 * @ingroup micro_path
 */
class MicroPathDeleteForm extends ContentEntityDeleteForm {
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete entity %id?', ['%id' => $this->entity->id()]);
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the micro path list.
   */
  public function getCancelUrl() {
    return new Url('entity.micro_path.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * Delete the entity and log the event. logger() replaces the watchdog.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->delete();

    $this->logger('micro_path')->notice('deleted %id.',
      [
        '%id' => $this->entity->id(),
      ]);
    // Redirect to domain path list after delete.
    $form_state->setRedirect('entity.micro_path.collection');
  }

}
