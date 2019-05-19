<?php

namespace Drupal\stacks\Widget;

use Drupal\Core;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\stacks\Entity\WidgetEntityType;
use Drupal\stacks\Entity\WidgetInstanceEntity;
use Drupal\stacks\Entity\WidgetEntity;
use Drupal\stacks\Widget;

/**
 * Class WidgetAutomaticTitleHandler.
 *
 * Used to set automatic titles. See hook_node_presave() implementation.
 */
class WidgetAutomaticTitleHandler {

  // The type of node this widget field is on.
  private $node_type = '';
  // The name of the widget field on this node.
  private $field_name = '';

  public function __construct(&$node) {
    $this->initiate($node);
  }

  /**
   * Send a $node object, it will scan all fields for the given node and will
   * start replacing titles.
   */
  private function initiate(&$node) {
    // Get all field instances for the given node
    $fields = $node->getFields();

    // Loop through all fields and see if there are any widget fields.
    foreach ($fields as $field) {
      if ($field->getFieldDefinition()->getType() == 'stacks_type') {
        // Set title for each widget instance.
        $this->field_name = $field->getName();
        $this->setAutomaticTitle($node);
      }
    }
  }


  /**
   * Grabs the items for this widget field from the node.
   *
   * @param $node
   */
  private function setAutomaticTitle(&$node) {

    if (empty($this->field_name)) {
      return;
    }

    $list = $node->get($this->field_name)->getValue();

    foreach ($list as $item) {
      $widget_instance_id = isset($item['widget_instance_id']) ? $item['widget_instance_id'] : FALSE;
      if ($widget_instance_id) {
        // Widget instance exists.
        $widget_instance = WidgetInstanceEntity::load($widget_instance_id);

        $is_reusable = $widget_instance->isShareable();

        // Check if the widget instance title is a wildcard
        if (!$is_reusable && !$widget_instance->label()) {
          // The new title format is:
          // - {node.title} - {widget.bundle} - {bundle delta}
          $title = $node->getTitle();
          $entity = $widget_instance->getWidgetEntity();
          $widget_entity_type = WidgetEntityType::load($entity->getType());
          $label = $widget_entity_type->label();
          $delta = $widget_instance_id;

          // Set widget title
          $widget_instance->setTitle("{$label} ({$widget_instance_id})");
          $widget_instance->save();
        }
      }
    }
  }
}
