<?php

/**
 * @file
 * Contains \Drupal\community_tasks\Form\CommitToTask.
 */

namespace Drupal\community_tasks\Form;

use \Drupal\Core\Form\FormStateInterface;
use Drupal\community_tasks\Element\TaskState;

/**
 * Builds a form to change the owner of a community task
 */
class CommitToTask extends CTaskActionBaseForm {

  var $target_state = TaskState::COMMITTED;

  /**
   * {@inheritdoc}
   */
  function getFormId() {
    return 'commit_to_task';
  }


  function name() {
    return t('Commit to this task');
  }

  /**
   * {@inheritdoc}
   */
  function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_state->getBuildInfo()['args'][0]
      ->setOwnerId(\Drupal::currentuser()->id())
      ->save();

    $form_state->setRedirect(
      'entity.node.canonical',
      ['node' => $node->id()]
    );
  }

}
