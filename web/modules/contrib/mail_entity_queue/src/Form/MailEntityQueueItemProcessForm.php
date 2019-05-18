<?php

namespace Drupal\mail_entity_queue\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to confirm a single queue item processing.
 */
class MailEntityQueueItemProcessForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\mail_entity_queue\Entity\MailEntityQueueItemInterface $entity */
    $entity = $this->getEntity();

    $success = $entity->queue()->getQueueProcessor()->processItem($entity);

    if ($success) {
      $message = $this->t('Queue item @id processed successfully.', [
        '@id' => $entity->id(),
      ]);
      $this->messenger()->addStatus($message);
    }
    else {
      $message = $this->t('Queue item @id failed processing.', [
        '@id' => $entity->id(),
      ]);
      $this->messenger()->addError($message);
    }

    $form_state->setRedirect('entity.mail_entity_queue_item.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.mail_entity_queue_item.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    /** @var \Drupal\mail_entity_queue\Entity\MailEntityQueueItemInterface $entity */
    $entity = $this->getEntity();

    return $this->t('Are you sure you want to process the item @id in queue @queue?', [
      '@id' => $entity->id(),
      '@queue' => $entity->queue()->label(),
    ]);
  }

}
