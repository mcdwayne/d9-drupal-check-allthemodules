<?php

namespace Drupal\flexible_list\Plugin\views\filter;

use Drupal\views\FieldAPIHandlerTrait;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\ViewExecutable;

/**
 * Filter handler which uses flexible-list-fields as options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("flexible_list_field")
 */
class FlexibleListField extends ManyToOne {

  use FieldAPIHandlerTrait;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $field_storage = $this->getFieldStorageDefinition();
    // Set valueOptions here so getValueOptions() will just return it.
    $this->valueOptions = flexible_list_allowed_values($field_storage);
  }

}
