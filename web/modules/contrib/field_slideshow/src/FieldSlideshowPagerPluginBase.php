<?php

namespace Drupal\field_slideshow;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Base class for field_slideshow_pager plugins.
 */
abstract class FieldSlideshowPagerPluginBase extends PluginBase implements FieldSlideshowPagerInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * Function render pager.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   List of items.
   *
   * @return array
   *   Rendered array.
   */
  abstract public function viewPager(FieldItemListInterface $items);

}
