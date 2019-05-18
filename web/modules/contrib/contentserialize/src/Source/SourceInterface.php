<?php

namespace Drupal\contentserialize\Source;

/**
 * Provides an interface defining a content import source.
 *
 * On iteration it should return serialized entities.
 *
 * @see \Drupal\contentserialize\SerializedEntity
 */
interface SourceInterface extends \Traversable {}
