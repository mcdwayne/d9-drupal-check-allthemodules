<?php

namespace Drupal\filterable_link\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkSeparateFormatter;

/**
 * Plugin implementation of the 'filterable_link_separate' formatter.
 *
 * @FieldFormatter(
 *   id = "filterable_link_separate",
 *   label = @Translation("Separate link text and URL"),
 *   field_types = {
 *     "filterable_link"
 *   }
 * )
 */

class FilterableLinkSeparateFormatter extends LinkSeparateFormatter {}