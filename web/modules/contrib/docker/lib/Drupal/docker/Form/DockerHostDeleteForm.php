<?php

/**
 * @file
 * DockerHost delete form.
 */

namespace Drupal\docker\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityNGConfirmFormBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the comment delete confirmation form.
 */
class DockerHostDeleteForm extends EntityNGConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the docker host %name?', array('%name' => $this->entity->name->value));
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);

    // @todo Convert to getCancelRoute() after http://drupal.org/node/1987778.
    $actions['cancel']['#href'] = 'node/' . $this->entity->nid->target_id;

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    // Delete the docker host and its replies.
    $this->entity->delete();
    drupal_set_message($this->t('The docker host has been deleted.'));
    watchdog('content', 'Deleted docker host @dhid.', array('@dhid' => $this->entity->id()));

    $form_state['redirect'] = "docker/hosts";
  }
}