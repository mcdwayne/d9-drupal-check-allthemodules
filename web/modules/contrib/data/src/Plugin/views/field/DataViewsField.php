<?php

namespace Drupal\data\Plugin\views\field;

use Drupal\views\Plugin\views\field\Field;

/**
 * Provides a views field to show specific column of data table.
 *
 * Set field handler class to custom, even if it has no differences
 * from the base class now, to avoid data breaking in the future.
 *
 * @ViewsField("data_column")
 */
class DataViewsField extends Field {

}
