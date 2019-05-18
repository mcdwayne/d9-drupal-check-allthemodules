<?php

namespace Drupal\community_tasks\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Provides a form element for selecting a transaction state.
 *
 * It inherits everything from radios but the trasaction states are autofilled.
 *
 * @FormElement("community_task_state")
 */
class TaskState extends RenderElement {

  const OPEN = 'open';
  const COMMITTED = 'committed';
  const COMPLETED = 'completed';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#nid' => 0,
      '#pre_render' => [
        [$class, 'preRender'],
      ],
    ];
  }

  /**
   * Process callback.
   */
  public static function preRender($element) {
    $element['#attached']['library'][] = 'community_tasks/icons';
    $node = Node::load($element['#nid']);
    $state = $node->ctask_state->value;
    $current_user = \Drupal::currentUser();

    $element['status'] = [
      '#type' => 'container',
      'description' => [
        '#markup' => static::stateDescription($state, $current_user, $node->getOwnerId()),
      ],
      '#attributes' => ['class' => ['ctaskstatus']]
    ];
    if ($form_class = static::getFormClass($state, $current_user, $node->getOwnerId())) {
      $element['status']['form'] = \Drupal::formBuilder()->getForm($form_class, $node);
    }

    return $element;
  }



  /**
   * Get a translated string describing the state of the task.
   * @param string $state
   * @param int $uid
   *   User id of the node owner
   *
   * @return TranslatedString
   */
  static function stateDescription($state, $current_user, $owner_uid) {
    if ($state == static::OPEN) {
      return t('Open');
    }
    $you = $owner_uid == \Drupal::currentUser()->id();
    if (!$you) {
      $name = User::load($owner_uid)->getDisplayName();
    }

    if($state == static::COMMITTED) {
      return $you ?
        t('You committed to this') :
        t('@name committed to this', ['@name' => $name]);
    }
    elseif($state == static::COMPLETED) {
      return $you ?
        t('Completed by you') :
        t('Completed by @name', ['@name' => $name]);
    }
  }

  static function getFormClass($state, $current_user, $owner_uid) {
    if ($state == static::OPEN) {
      if ($current_user->hasPermission('commit to tasks')) {
        $class = 'CommitToTask';
      }
    }
    elseif ($state == static::COMMITTED) {
      if ($owner_uid == $current_user->id()) {
        $class = 'UncommitToTask';
      }
      elseif ($current_user->hasPermission('edit any community_task content')) {
        $class = 'SignTask';
      }
    }
    if (isset($class)) {
      return '\Drupal\community_tasks\Form\\'. $class;
    }

  }


}
