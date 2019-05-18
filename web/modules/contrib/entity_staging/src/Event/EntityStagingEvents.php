<?php

namespace Drupal\entity_staging\Event;

/**
 * Defines events for the entity_staging module.
 */
final class EntityStagingEvents {

  /**
   * Event fired when the migration field process definition is created.
   *
   * @var string
   */
  const PROCESS_FIELD_DEFINITION = 'entity_staging.create_migration_process_field_definition';

  /**
   * Event fired before export doing.
   *
   * @var string
   */
  const BEFORE_EXPORT = 'entity_staging.before_export';

}
