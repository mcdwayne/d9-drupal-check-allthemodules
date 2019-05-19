<?php

/**
 * @file
 * Contains \Drupal\social_counters\Form\SocialCountersConfigDeleteForm.
 */

namespace Drupal\social_counters\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a social_counters entity.
 */
class SocialCountersConfigDeleteForm extends EntityConfirmFormBase {
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete entity %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the social counters list.
   */
  public function getCancelURL() {
    return new Url('entity.social_counters_config.collection');
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

    $this::logger('social_counters')->notice('@type: deleted %title.',
      array(
        '@type' => $entity->bundle(),
        '%title' => $entity->label(),
      ));
    $form_state->setRedirect('entity.social_counters_config.collection');
  }
}
