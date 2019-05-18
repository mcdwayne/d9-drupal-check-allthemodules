<?php

namespace Drupal\flexible_list\Plugin\views\argument;

use Drupal\Core\Field\AllowedTagsXssTrait;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\FieldAPIHandlerTrait;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Drupal\options\Plugin\views\argument\NumberListField;

/**
 * Argument handler for flexible list field to show the human readable name in the
 * summary.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("flexible_list_number_field")
 */
class FlexibleListNumberField extends NumberListField {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $field_storage = $this->getFieldStorageDefinition();
    $this->allowedValues = flexible_list_allowed_values($field_storage);
  }

}
