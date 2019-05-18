<?php

/**
 * @file
 * Contains \Drupal\push_notifications\Form\PushNotificationsTokenDeleteForm.
 */

namespace Drupal\push_notifications\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a push_notifications_token entity.
 *
 * @ingroup push_notifications_token
 */
class PushNotificationsTokenDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the token %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('push_notifications.token.collection');
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
   * Delete the entity and log the event
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->delete();

    \Drupal::logger('push_notifications')->notice('Deleted %title push notifications device token',
      array(
        '%title' => $this->entity->label(),
      ));
    $form_state->setRedirect('push_notifications.token.collection');
  }
}