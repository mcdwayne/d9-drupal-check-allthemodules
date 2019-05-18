<?php

namespace Drupal\asf;

use Drupal\asf\AsfAction;
use Drupal\asf\AsfSchema;
use Drupal\asf\Event\AsfNodeEvent;
use Drupal\asf\Event\AsfEvents;
use Drupal\Core\Entity\Entity;
use Drupal\node\Entity\Node;


/**
 * Class AsfSchema.
 */
class AsfCron {

  /**
   *
   */
  function cleanup() {
    $options = array(
      'time' => array(
        'value' => strtotime('NOW - 7 days'),
        'operator' => '<'
      ),
      'status' => ASF_STATUS_EXECUTED
    );
    $executed_actions = AsfSchema::selectActions($options);
    $a = new AsfAction();
    foreach ($executed_actions as $executed_action) {
      $a->delete($executed_action->aid);
    }
  }

  /**
   *
   */
  function selectActions() {
    $options = array(
      'time' => array(
        'value' => REQUEST_TIME,
        'operator' => '<'
      ),
      'status' => ASF_STATUS_PENDING
    );
    $actions = AsfSchema::selectActions($options);
    return $actions;
  }


  /**
   * @param $actions
   * @return array
   */
  function deduceTodos($actions) {
    $todos = array();
    foreach ($actions as $action) {
      $todos[$action->eid] = array(
        'eid' => $action->eid,
        'action' => $action->action
      );
    }
    return $todos;
  }


  /**
   *
   */
  function discardActions($actions) {
    foreach ($actions as $action) {
      $a = new AsfAction();
      $a->dismiss($action);
    }
  }


  /**
   *
   */
  function runCron() {
    $actions = $this->selectActions();
    $todos = $this->deduceTodos($actions);
    $this->discardActions($actions);
    //LATER do this per action as it is finished minor improvement.
    if (!empty($todos)) {
      \Drupal::logger('asf')
        ->notice('Actions that will take place: <pre>' . print_r($todos, TRUE) . '</pre>');
    }
    foreach ($todos as $todo) {
      $this->doSingleCronAction($todo);
    }
  }


  /**
   * Do a single Action.
   * $todo['eid'] = entity_id
   * $todo['action'] = publish|unpublish
   */
  function doSingleCronAction($todo) {
    // @todo: rework this so other entities can be published/unpublished too.
    $node = Node::load($todo['eid']);

    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event = new AsfNodeEvent($node);
    $event_dispatcher->dispatch(AsfEvents::CRON_ACTION_NODE, $event);

    if ($node instanceof Node) {
      switch ($todo['action']) {
        case ASF_ACTION_PUBLISH:
          $event_dispatcher->dispatch(AsfEvents::NODE_PUBLISH, $event);
          break;

        case ASF_ACTION_UNPUBLISH:
          $event_dispatcher->dispatch(AsfEvents::NODE_UNPUBLISH, $event);
          break;
      }
    }
  }
}
