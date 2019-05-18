<?php


namespace Drupal\eqsf;

use Drupal\eqsf\EqsfAction;
use Drupal\eqsf\EqsfSchema;
use Drupal\Component\Datetime;
/**
 * Class EqsfCron.
 */
class EqsfCron {

  /**

   */
  function cleanup() {

    $options = array(
      'stop' => array(
        'value'    => strtotime('NOW - 7 days'),
        'operator' => '<'
      ),
    );
    $executed_actions = EqsfSchema::selectActions($options);
    $a = new  EqsfAction();

    foreach ($executed_actions as $executed_action) {
      $a->delete($executed_action->eid);
    }
  }
  /**
   *
   */
  function runCron() {
    $published_actions = $this->selectPublishedActions();
    $unpublished_actions = $this->selectUnpublishedActions();

    $published_todos = $this->deduceTodos($published_actions);
    $unpublished_todos = $this->deduceTodos($unpublished_actions);

    //LATER do this per action as it is finished minor improvement.
    if (!empty($published_todos)) {
      foreach ($published_todos as $todo) {
        //$entity = \Drupal\node\Entity\Node::load($todo['eid']);
        //$this->doPublishAction($todo['eid'], $todo['eqid']);
        $this->doPublishAction($todo['eid'], $todo['eqid'], $todo['position']);
      }
    }
    if (!empty($unpublished_todos)) {
      foreach ($unpublished_todos as $todo) {
        //$entity = \Drupal\node\Entity\Node::load($todo['eid']);
        $this->doUnpublishAction($todo['eid'], $todo['eqid']);
      }
    }
  }

  /**
   *
   */
  function selectPublishedActions() {
    $options = array(
      'start' => array(
        'value'    => \Drupal::time()->getRequestTime(),
        'operator' => '<'
      ),
      'stop'  => array(
        'value'    => \Drupal::time()->getRequestTime(),
        'operator' => '>'
      ),
    );
    $actions = EqsfSchema::selectActions($options);
    return $actions;
  }

  function selectUnpublishedActions() {
    $options = array(
      'start' => array(
        'value'    => \Drupal::time()->getRequestTime(),
        'operator' => '>'
      ),
    );
    $actions_start = EqsfSchema::selectActions($options);
    $options = array(
      'stop' => array(
        'value'    => \Drupal::time()->getRequestTime(),
        'operator' => '<'
      ),
    );
    $actions_stop = EqsfSchema::selectActions($options);
    $actions = array_merge($actions_start, $actions_stop);
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
        'eid'      => $action->eid,
        'eqid'     => $action->eqid,
        'position' => $action->position,
      );
    }
    return $todos;
  }

  /**
   * Unpublish one item.
   */
  function doUnpublishAction($eid, $eqid) {
    //add a function to throw the node out of the queue
    $entity_subqueue = \Drupal::entityTypeManager()
      ->getStorage('entity_subqueue')
      ->load($eqid);
    $items = $entity_subqueue->get('items')->getValue();

    foreach ($items as $key => $item) {
      if ($item['target_id'] == $eid) {
        unset($items[$key]);
      }
    }

    $entity_subqueue->set('items', $items);
    $entity_subqueue->save();
  }

  /**
   * Publish one item
   * @param $entity
   */
  function doPublishAction($eid, $eqid, $position = NULL) {
    $entity_subqueue = \Drupal::entityTypeManager()
      ->getStorage('entity_subqueue')
      ->load($eqid);
    $items = $entity_subqueue->get('items')->getValue();

    if (empty($items)) {
      $items[] = ['target_id' => $eid];
    }
    else {
      $existing_entity_index = array_search($eid, array_column($items, 'target_id'));
      if ($existing_entity_index !== FALSE) {
        unset($items[$existing_entity_index]);
      }
      if ($position <= 0) {
        array_unshift($items, ['target_id' => $eid]);
      }
      else {
        $items[] = ['target_id' => $eid];
      }
    }

    $entity_subqueue->set('items', $items);
    $entity_subqueue->save();
  }
}
