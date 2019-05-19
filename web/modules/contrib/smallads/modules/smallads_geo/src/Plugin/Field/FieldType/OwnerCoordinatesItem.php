<?php

namespace Drupal\smallads_geo\Plugin\Field\FieldType;

use Drupal\geofield\Plugin\Field\FieldType\GeofieldItem;

/**
 * Defines the computed entity field type which holds the entity Owners coordinates
 *
 *
 * @FieldType(
 *   id = "owner_coordinates",
 *   label = @Translation("Entity Owner geofield location"),
 *   description = @Translation("Inherit the location from the entity owner"),
 *   default_formatter = "geofield_default",
 *   list_class = "\Drupal\smallads_geo\Plugin\Field\FieldType\OwnerCoordinatesItemList"
 * )
 *
 */
class OwnerCoordinatesItem extends GeofieldItem {

}
