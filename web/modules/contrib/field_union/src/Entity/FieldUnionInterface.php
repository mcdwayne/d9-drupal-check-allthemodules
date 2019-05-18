<?php

namespace Drupal\field_union\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines a class for a field union entity.
 *
 * @ConfigEntityType(
 *   id = "field_union",
 *   label = @Translation("Field Union"),
 *   label_collection = @Translation("Field unions"),
 *   label_singular = @Translation("field union"),
 *   label_plural = @Translation("field unions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count field union",
 *     plural = "@count field unions",
 *   ),
 *   config_prefix = "field_union",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "fields",
 *   }
 * )
 */
interface FieldUnionInterface extends ConfigEntityInterface {

  /**
   * Gets value of Description.
   *
   * @return string
   *   Value of Description.
   */
  public function getDescription();

  /**
   * Gets value of Fields.
   *
   * @return array
   *   Value of Fields.
   */
  public function getFields();

}
