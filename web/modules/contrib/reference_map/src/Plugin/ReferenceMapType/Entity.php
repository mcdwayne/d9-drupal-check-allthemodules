<?php

namespace Drupal\reference_map\Plugin\ReferenceMapType;

use Drupal\reference_map\Plugin\ReferenceMapTypeBase;

/**
 * Plugin implementation of the Entity reference map type.
 *
 * @ReferenceMapType(
 *   id = "entity",
 *   title = @Translation("Entity"),
 *   help = @Translation("A map type that finds a destination entity given a source entity.")
 * )
 */
class Entity extends ReferenceMapTypeBase {

}
