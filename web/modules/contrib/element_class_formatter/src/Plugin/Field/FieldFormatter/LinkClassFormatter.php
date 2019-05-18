<?php

namespace Drupal\element_class_formatter\Plugin\Field\FieldFormatter;

use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Plugin implementation of the 'link with class' formatter.
 *
 * @FieldFormatter(
 *   id = "link_class",
 *   label = @Translation("Link (with class)"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkClassFormatter extends LinkFormatter {

  use ElementLinkClassTrait;

}
