<?php

namespace Drupal\asf\Event;

/**
 * Defines events for ASF module.
 */
final class AsfEvents {

  /**
   * The name of the event fired when a cron action is applied on a node object.
   *
   * This event allows you to perform custom actions whenever a cron action
   * is applied on a node object.
   *
   * @Event
   */
  const CRON_ACTION_NODE = 'asf.cron_action_node';

  /**
   * The name of the event fired when a publish action is applied on a node.
   *
   * This event allows you to perform custom actions whenever a publish action
   * is applied on a node object.
   *
   * @Event
   */
  const NODE_PUBLISH = 'asf.node_publish';

  /**
   * The name of the event fired when a unpublish action is applied on a node.
   *
   * This event allows you to perform custom actions whenever an
   * unpublish action is applied on a node object.
   *
   * @Event
   */
  const NODE_UNPUBLISH = 'asf.node_unpublish';

}
