<?php

namespace Drupal\content_entity_builder\Plugin\views\filter;

use Drupal\views\FieldAPIHandlerTrait;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\ViewExecutable;

/**
 * Filter handler which uses list-fields as options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("list_base_field")
 */
class ListBaseField extends ManyToOne {

  use FieldAPIHandlerTrait;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    //Support base field
	if(!isset($this->definition['field_name']) && isset($this->definition['entity field'])){
      $this->definition['field_name'] = $this->definition['entity field'];
	}
    $field_storage = $this->getFieldStorageDefinition();
	$this->valueOptions = options_allowed_values($field_storage);

  }

}
