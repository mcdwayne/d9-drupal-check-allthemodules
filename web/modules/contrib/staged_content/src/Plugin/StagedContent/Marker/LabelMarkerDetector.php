<?php

namespace Drupal\staged_content\Plugin\StagedContent\Marker;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Simple custom icon set for this project.
 *
 * @MarkerDetector(
 *   id = "label"
 * )
 */
class LabelMarkerDetector implements MarkerDetectorInterface {

  /**
   * {@inheritdoc}
   */
  public function detectMarker(ContentEntityInterface $entity, array $markers = []) {
    // If this item uses markers (to distinguish between prod/acc/test/dev
    // content etc. Detect the marker in the label here.
    // This only applies for "top level" items currently. So only the actual
    // items belonging to this set are validated. Not those connected to them.
    // For users we'll check or the email is in the form
    // USERNAME+MARKER..@SOMETHING.com.
    if ($entity->getEntityTypeId() == 'user') {
      // @var \Drupal\user\UserInterface $entity
      foreach ($markers as $marker) {
        if (strpos($entity->getEmail(), '+' . $marker) !== FALSE) {
          return $marker;
        }
      }
    }
    // @TODO Handle the menu items in a more solid way.
    else {
      foreach ($markers as $marker) {
        if (strpos($entity->label(), '+' . $marker) !== FALSE) {
          return $marker;
        }
      }
    }
    return MarkerDetectorInterface::UNDEFINED;
  }

}
