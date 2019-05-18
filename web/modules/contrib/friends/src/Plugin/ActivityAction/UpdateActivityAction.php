<?php

namespace Drupal\friends\Plugin\ActivityAction;

use Drupal\activity_creator\Plugin\ActivityActionBase;

/**
 * Provides a 'UpdateActivityAction' activity action.
 *
 * @ActivityAction(
 *  id = "update_entity_action",
 *  label = @Translation("Action that is triggered when an entity is updated"),
 * )
 */
class UpdateActivityAction extends ActivityActionBase {

  /**
   * {@inheritdoc}
   */
  public function create($entity) {

    if ($this->isValidEntity($entity)) {
      $this->createMessage($entity);
    }
  }

}
