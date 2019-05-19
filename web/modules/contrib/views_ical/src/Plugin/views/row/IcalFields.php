<?php

/**
 * @file
 * Contains \Drupal\views_ical\Plugin\views\row\Fields.
 */

namespace Drupal\views_ical\Plugin\views\row;

use Drupal\views\Plugin\views\row\Fields;

/**
 * The 'Ical Fields' row plugin
 *
 * This displays fields one after another, giving options for inline
 * or not.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "ical_fields",
 *   title = @Translation("iCal Fields"),
 *   help = @Translation("Displays the fields with a template."),
 *   theme = "views_view_ical_fields",
 *   display_types = {"feed"}
 * )
 */
class IcalFields extends Fields {
}
