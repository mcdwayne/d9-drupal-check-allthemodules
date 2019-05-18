<?php

namespace Drupal\gridstack\Plugin\Field\FieldFormatter;

/**
 * Plugin implementation of the 'GridStack Image' formatter.
 *
 * @FieldFormatter(
 *   id = "gridstack_image",
 *   label = @Translation("GridStack Image"),
 *   description = @Translation("Display the images as a GridStack."),
 *   field_types = {"image"},
 *   quickedit = {"editor" = "disabled"}
 * )
 */
class GridStackImageFormatter extends GridStackFileFormatterBase {

  use GridStackFormatterTrait;

}
