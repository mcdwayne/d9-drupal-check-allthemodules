<?php
/**
 * @file
 * Contains \Drupal\nodeletter\Form\NodeletterSendingDeleteForm.
 */

namespace Drupal\nodeletter\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\nodeletter\Entity\NodeletterSending;

/**
 * Provides a form for deleting a content_entity_example entity.
 *
 * @ingroup content_entity_example
 */
class NodeletterSendingDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    /** @var NodeletterSending $entity */
    $entity = $this->getEntity();
    return $this->t(
      'Are you sure you want to delete information of sending @id for %node?',
      [
        '@id' => $entity->getSendingId(),
        '%node' => $entity->getNode()->label(),
      ]
    );
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the contact list.
   */
  public function getCancelURL() {
    return new Url('entity.nodeletter_sending.collection');
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
    /** @var NodeletterSending $entity */
    $entity = $this->getEntity();
    $entity->delete();

    \Drupal::logger('nodeletter')->notice('Deleted information about sending @id of node %node.',
      array(
        '@id' => $entity->getSendingId(),
        '%node' => $entity->getNode()->link(),
      ));
    $form_state->setRedirect('entity.nodeletter_sending.collection');
  }
}
