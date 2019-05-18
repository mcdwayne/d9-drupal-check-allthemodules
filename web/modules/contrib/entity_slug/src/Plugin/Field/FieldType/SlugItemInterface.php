<?php

namespace Drupal\entity_slug\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Abstract base class SlugItemBase.
 *
 * @package Drupal\entity_slug\Plugin\Field\FieldType
 */
interface SlugItemInterface extends FieldItemInterface {

  /**
   * Handles calling slugification service.
   *
   * @param string $input
   *   The input to slugify.
   *
   * @return string
   *   The slugified string.
   */
  public function slugify($input);

  /**
   * Gets an array of enabled Slugifier plugins for this field item.
   *
   * @return \Drupal\entity_slug\Plugin\Slugifier\SlugifierInterface[]
   *   The Slugifier plugins.
   */
  public function getSlugifiers();
}
