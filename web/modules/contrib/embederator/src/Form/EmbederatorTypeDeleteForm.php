<?php

namespace Drupal\embederator\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting an embederator_type entity.
 *
 * @ingroup embederator
 */
class EmbederatorTypeDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete entity %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the proxy list.
   */
  public function getCancelUrl() {
    return new Url('entity.embederator_type.collection');
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

    $this->logger('embederator')->notice('Deleted %title.',
      [
        '%title' => $this->entity->label(),
      ]);
    $form_state->setRedirect('entity.embederator_type.collection');
  }

}
