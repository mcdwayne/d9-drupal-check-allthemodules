<?php

namespace Drupal\entity_slug\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetInterface;

interface SlugWidgetInterface extends WidgetInterface {
  /**
   * Gets the information items to display in a list under the widget.
   *
   * @param FieldItemListInterface $slugItems
   *   The field item list.
   *
   * @return array
   *   The items to display in the information list.
   */
  public function getInformation(FieldItemListInterface $slugItems);
}
