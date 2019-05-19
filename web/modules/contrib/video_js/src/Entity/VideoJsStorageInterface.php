<?php

namespace Drupal\video_js\Entity;

use Drupal\Core\Entity\EntityBundleListenerInterface;
use Drupal\Core\Entity\Schema\DynamicallyFieldableEntityStorageSchemaInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;

/**
 * Provides an interface defining an crop storage controller.
 */
interface VideoJsStorageInterface extends SqlEntityStorageInterface, DynamicallyFieldableEntityStorageSchemaInterface, EntityBundleListenerInterface {

}
