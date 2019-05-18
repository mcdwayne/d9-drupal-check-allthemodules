<?php

namespace Drupal\library_select\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;

/**
 * Plugin implementation of the 'library_select_widget' widget.
 *
 * @FieldWidget(
 *   id = "library_select_widget",
 *   label = @Translation("Library Select"),
 *   field_types = {
 *     "library_select_field"
 *   },
 *   multiple_values = TRUE
 * )
 */
class LibrarySelectWidget extends OptionsSelectWidget {

}
