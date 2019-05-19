<?php

namespace Drupal\yaml_content\Event;

/**
 * Defines events for the YAML Content framework.
 *
 * @see \Drupal\yaml_content\Event\PreImportEvent
 * @see \Drupal\yaml_content\Event\PostImportEvent
 * @see \Drupal\yaml_content\Event\ContentParsedEvent
 * @see \Drupal\yaml_content\Event\EntityPreSaveEvent
 * @see \Drupal\yaml_content\Event\EntityPostSaveEvent
 * @see \Drupal\yaml_content\Event\EntityImportEvent
 * @see \Drupal\yaml_content\Event\FieldImportEvent
 */
final class YamlContentEvents {

  /**
   * Name of the event fired when beginning an import operation.
   *
   * This event allows modules to perform an action whenever a content import
   * operation is about to begin. The event listener receives a
   * \Drupal\yaml_content\Event\PreImportEvent instance.
   *
   * @Event
   *
   * @see \Drupal\yaml_content\Event\PreImportEvent
   *
   * @var string
   */
  const PRE_IMPORT = 'yaml_content.import.pre_import';

  /**
   * Name of the event fired when finishing an import operation.
   *
   * This event allows modules to perform an action whenever a content import
   * operation is completing. The event listener receives a
   * \Drupal\yaml_content\Event\PostImportEvent instance.
   *
   * @Event
   *
   * @see \Drupal\yaml_content\Event\PostImportEvent
   *
   * @var string
   */
  const POST_IMPORT = 'yaml_content.import.post_import';

  /**
   * Name of the event fired when a content file is parsed.
   *
   * This event allows modules to perform an action whenever a content file
   * is parsed for import. The event listener receives a
   * \Drupal\yaml_content\Event\ContentParsedEvent instance.
   *
   * @Event
   *
   * @see \Drupal\yaml_content\Event\ContentParsedEvent
   *
   * @var string
   */
  const CONTENT_PARSED = 'yaml_content.import.content_parsed';

  /**
   * Name of the event fired before an imported entity is saved.
   *
   * This event allows modules to perform an action whenever an entity is
   * prepared, but before it is saved. The event listener receives a
   * \Drupal\yaml_content\Event\EntityPreSaveEvent instance.
   *
   * @Event
   *
   * @see \Drupal\yaml_content\Event\EntityPreSaveEvent
   */
  const ENTITY_PRE_SAVE = 'yaml_content.import.entity_pre_save';

  /**
   * Name of the event fired after an imported entity is saved.
   *
   * This event allows modules to perform an action whenever an entity is
   * imported and saved. The event listener receives a
   * \Drupal\yaml_content\Event\EntityPostSaveEvent instance.
   *
   * @Event
   *
   * @see \Drupal\yaml_content\Event\EntityPostSaveEvent
   */
  const ENTITY_POST_SAVE = 'yaml_content.import.entity_post_save';

  /**
   * Name of the event fired before entity data is imported as a created entity.
   *
   * This event allows modules to perform an action whenever entity data is
   * about to be interpreted created as an entity. The event listener receives
   * a \Drupal\yaml_content\Event\EntityImportEvent instance.
   *
   * @Event
   *
   * @see \Drupal\yaml_content\Event\EntityImportEvent
   */
  const IMPORT_ENTITY = 'yaml_content.import.import_entity';

  /**
   * Name of the event fired before entity field data is imported and assigned.
   *
   * This event allows modules to perform an action whenever field data is about
   * to be interpreted and populated into an entity field. The event listener
   * receives a \Drupal\yaml_content\Event\FieldImportEvent instance.
   *
   * @Event
   *
   * @see \Drupal\yaml_content\Event\FieldImportEvent
   */
  const IMPORT_FIELD = 'yaml_content.import.import_field';

}
