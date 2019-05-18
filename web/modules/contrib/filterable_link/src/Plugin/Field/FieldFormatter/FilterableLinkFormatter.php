<?php

namespace Drupal\filterable_link\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Plugin implementation of the 'filterable_link_view' formatter.
 *
 * @FieldFormatter(
 *   id = "filterable_link_view",
 *   label = @Translation("Link"),
 *   field_types = {
 *     "filterable_link"
 *   }
 * )
 */

class FilterableLinkFormatter extends LinkFormatter {}