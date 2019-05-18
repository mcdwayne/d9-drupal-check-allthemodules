<?php

namespace Drupal\crm_core_activity;

use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Defines the interface for activity type plugins.
 */
interface ActivityTypePluginInterface extends ConfigurablePluginInterface {

  /**
   * Displays the contents of the label field on the activity entity.
   *
   * @param \Drupal\crm_core_activity\ActivityInterface $entity
   *   The activity entity to build the label for.
   *
   * @return string
   *   Returns the entity label.
   */
  public function label(ActivityInterface $entity);

  /**
   * Returns visual representation of the activity in form of a render array.
   *
   * @param \Drupal\crm_core_activity\ActivityInterface $entity
   *   The activity entity to build the label for.
   *
   * @return array
   *   Visual representation of the activity in form of a render array.
   */
  public function display(ActivityInterface $entity);

}
