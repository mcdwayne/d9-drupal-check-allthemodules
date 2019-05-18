<?php

/**
 * @file
 * Contains \Drupal\community_tasks\Form\UncommitToTask.
 */

namespace Drupal\community_tasks\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\community_tasks\Element\TaskState;

/**
 * Builds a form to revert the task owner to user 1
 *
 * @todo this form should trigger an email to the node owner.
 */
class UncommitToTask extends CTaskActionBaseForm {

  var $target_state = TaskState::OPEN;

  function name() {
    return t('Un-commit from this task');
  }

  /**
   * {@inheritdoc}
   */
  function getFormId() {
    return 'uncommit_to_task';
  }

  /**
   * {@inheritdoc}
   */
  function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_state->getBuildInfo()['args'][0]
      ->save();

    $form_state->setRedirect(
      'entity.node.canonical',
      ['node' => $node->id()]
    );
    $this->logger->notice(
        '@user uncommitted from task @nid',
        [
          '@user' => $node->getOwner()->getDisplayName(),
          '@nid' => $nid
        ]
    );
  }

}
