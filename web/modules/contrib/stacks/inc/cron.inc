<?php

/**
 * @file
 * Contains cron.inc.
 */

use Drupal\stacks\Entity\WidgetInstanceEntity;

/**
 * Implements hook_cron().
 *
 * We need to delete all "ghost" required widget instances and widget entities. These
 * are entities created when loading a create node form, but never saving the form.
 */
function stacks_cron() {
  // We had some logic here but it had some potential to remove
  // stacks widgets that ere not supposed to be removed.
  // @TODO: Improve on the previous logic making sure that doesn't happen.
}

/**
 * Function for deleting all stacks and widget instances.
 */
function stacks_clear_all() {

  $deleted = 0;

  $query = db_query('SELECT wi.id FROM {widget_instance_entity} wi');

  // Delete each ghost entity.
  foreach ($query as $record) {
    // Get the widget instance.
    $widget_instance = WidgetInstanceEntity::load($record->id);
    $widget_entity = $widget_instance->getWidgetEntity();

    // Delete the entities.
    $widget_instance->delete();

    // Make sure the stacks entity exists.
    if (method_exists($widget_entity, 'delete')) {
      $widget_entity->delete();
    }

    $deleted++;
  }

  \Drupal::logger('stacks')
    ->info('Deleted ' . $deleted . ' widget instance entities.');
}
