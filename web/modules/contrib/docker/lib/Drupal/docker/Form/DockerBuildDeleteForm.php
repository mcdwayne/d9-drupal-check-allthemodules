<?php

/**
 * @file
 * DockerBuild delete form.
 */

namespace Drupal\docker\Form;

use Drupal\Core\Entity\EntityNGConfirmFormBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the comment delete confirmation form.
 */
class DockerBuildDeleteForm extends EntityNGConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the docker build %name?', array('%name' => $this->entity->name->value));
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
    // Delete the docker build and its files.
    $this->entity->delete();
    drupal_set_message($this->t('The build and all related files have been deleted.'));
    watchdog('content', 'Deleted docker build @dbid and its files.', array('@dbid' => $this->entity->id()));
    $form_state['redirect'] = "docker/builds";
  }
}