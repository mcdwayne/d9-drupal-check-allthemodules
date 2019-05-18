<?php

namespace Drupal\coming_soon\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a content_entity_example entity.
 *
 * @ingroup content_entity_example
 */
class SubscriberDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this subscriber "%email"?', ['%email' => $this->entity->email->value]);
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the contact list.
   */
  public function getCancelUrl() {
    return new Url('entity.coming_soon_subscriber.collection');
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
   * Delete the entity and log the event. log() replaces the watchdog.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->delete();

    \Drupal::logger('coming_soon')->notice('@type: deleted %email.',
      [
        '@type' => $this->entity->bundle(),
        '%email' => $this->entity->email->value,
      ]);
    $form_state->setRedirect('entity.coming_soon_subscriber.collection');
  }

}
