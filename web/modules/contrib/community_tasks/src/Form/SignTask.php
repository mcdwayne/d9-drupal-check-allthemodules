<?php

/**
 * @file
 * Contains \Drupal\community_tasks\Form\SignTask.
 */

namespace Drupal\community_tasks\Form;

use Drupal\community_tasks\Element\TaskState;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a form to promote a community task node
 */
class SignTask extends CTaskActionBaseForm {

  var $target_state = TaskState::COMPLETED;

  /**
   * {@inheritdoc}
   */
  function getFormId() {
    return 'sign_task';
  }


  function name() {
    return t('Mark this task completed');
  }


  /**
   * {@inheritdoc}
   */
  function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $node = $form_state->getBuildInfo()['args'][0];
    $node->save();

    $form_state->setRedirect(
      'entity.user.canonical',
      ['user' => $node->getOwnerId()]
    );
  }

}
