<?php

namespace Drupal\staged_content\Plugin\StagedContent\Marker;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for the various ways of detecting a marker.
 */
interface MarkerDetectorInterface {

  /**
   * Constant to identify an undefined marker.
   */
  const UNDEFINED = 'prod';

  /**
   * Gather the data for a content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to handle.
   * @param string[] $markers
   *   Any markers to look for.
   *
   * @return string
   *   The detected marker or undefined if none was found.
   */
  public function detectMarker(ContentEntityInterface $entity, array $markers = []);

}
