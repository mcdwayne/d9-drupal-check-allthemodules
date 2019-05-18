<?php

namespace Drupal\element_class_formatter\Plugin\Field\FieldFormatter;

use Drupal\telephone\Plugin\Field\FieldFormatter\TelephoneLinkFormatter;

/**
 * Plugin implementation of the 'telephone_link_class' formatter.
 *
 * @FieldFormatter(
 *   id = "telephone_link_class",
 *   label = @Translation("Telephone link (with class)"),
 *   field_types = {
 *     "telephone"
 *   }
 * )
 */
class TelephoneLinkClassFormatter extends TelephoneLinkFormatter {

  use ElementLinkClassTrait;

}
