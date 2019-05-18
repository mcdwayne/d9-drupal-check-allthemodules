<?php

namespace Drupal\entity_usage\Events;

/**
 * Contains all events thrown by Entity Usage.
 */
final class Events {

  /**
   * The USAGE_ADD event occurs when entities are referenced.
   *
   * @var string
   */
  const USAGE_ADD = 'entity_usage.add';

  /**
   * The USAGE_DELETE event occurs when reference to an entity is removed.
   *
   * @var string
   */
  const USAGE_DELETE = 'entity_usage.delete';

  /**
   * The BULK_TARGETS_DELETE event.
   *
   * The BULK_TARGETS_DELETE event occurs when all records of a given
   * entity_type (target) is removed.
   *
   * @var string
   */
  const BULK_TARGETS_DELETE = 'entity_usage.bulk_targets_delete';

  /**
   * The BULK_HOSTS_DELETE event.
   *
   * The BULK_HOSTS_DELETE event occurs when all records of a given entity_type
   * (host) are removed.
   *
   * @var string
   */
  const BULK_HOSTS_DELETE = 'entity_usage.bulk_delete_hosts';

}
