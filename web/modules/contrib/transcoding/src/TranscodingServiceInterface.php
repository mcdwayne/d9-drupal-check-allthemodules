<?php

namespace Drupal\transcoding;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Provides an interface for defining Transcoding service entities.
 */
interface TranscodingServiceInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

}
