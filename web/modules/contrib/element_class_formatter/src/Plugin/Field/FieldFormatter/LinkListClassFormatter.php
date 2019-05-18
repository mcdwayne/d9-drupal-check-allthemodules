<?php

namespace Drupal\element_class_formatter\Plugin\Field\FieldFormatter;

use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Formatter for displaying links in an HTML list.
 *
 * @FieldFormatter(
 *   id="link_list_class",
 *   label="Link list (with class)",
 *   field_types={
 *     "link",
 *   }
 * )
 */
class LinkListClassFormatter extends LinkFormatter {

  use ElementListClassTrait;

}
