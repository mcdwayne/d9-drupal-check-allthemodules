<?php

namespace Drupal\schema_book\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_book_id' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_book_id",
 *   label = @Translation("@id"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. Globally unique @id, usually a url, used to to link other properties to this object."),
 *   name = "@id",
 *   group = "schema_book",
 *   weight = -1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class SchemaBookId extends SchemaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
