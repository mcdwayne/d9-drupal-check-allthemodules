<?php

namespace Drupal\stacks\Widget;

use Drupal\Core;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\stacks\Entity\WidgetInstanceEntity;
use Drupal\stacks\Entity\WidgetEntity;
use Drupal\stacks\Widget;

/**
 * Class WidgetFieldHandlers.
 *
 * Used to attach required fields. See hook_node_presave() implementation.
 */
class WidgetRequiredFields {

  // The type of node this widget field is on.
  private $node_type = '';
  // The name of the widget field on this node.
  private $field_name = '';
  // These are the form settings for the widget field.
  private $settings_required = [];
  private $settings_required_loc_optional = [];

  // Did this get changed? If so we need to save the node.
  private $changed = FALSE;

  public function __construct(&$node) {
    $this->initiate($node);
  }

  /**
   * Send a $node object, it will determine if there are any widget fields on this
   * node and auto add any required stacks to the top.
   */
  private function initiate(&$node) {
    // Reverse the order of the fields, since our field will be towards the bottom.
    // if it is there.
    $fields = array_reverse($node->getFields());

    // Loop through all fields and see if there are any widget fields.
    foreach ($fields as $field) {
      if ($field->getFieldDefinition()->getType() == 'stacks_type') {
        // Add any necessary required fields.
        $this->defineSettings($node, $field);
        $this->handleRequiredFieldsOnNode($node);
      }
    }
  }

  /**
   * Grabs the settings for the required widget bundles for a certain field on
   * a certain content type.
   *
   * @param $node
   * @param $field Make sure to only send widget fields.
   */
  private function defineSettings($node, $field) {
    $this->node_type = $node->getType();
    $this->field_name = $field->getFieldDefinition()->getName();

    $bundle_settings = EntityFormDisplay::load('node.' . $this->node_type . '.default')
      ->getComponent($this->field_name)['settings'];
    $this->settings_required = (isset($bundle_settings['bundles_required_pos_locked']) ? $bundle_settings['bundles_required_pos_locked'] : []);
    $this->settings_required_loc_optional = (isset($bundle_settings['bundles_required_pos_optional']) ? $bundle_settings['bundles_required_pos_optional'] : []);
  }

  /**
   * Grabs the items for this widget field from the node.
   *
   * @param $node
   */
  private function handleRequiredFieldsOnNode(&$node) {

    if (empty($this->field_name)) {
      return;
    }

    // If the $_POST variable for this field exists, and if the node id doesn't
    // exist, we know this is a duplicate call for a new node and we use those
    // values.
    if (is_null($node->id()) && isset($_POST[$this->field_name])) {
      $node->get($this->field_name)->setValue($_POST[$this->field_name]);
      return;
    }

    $list = $node->get($this->field_name)->getValue();

    $required_on_node = [];
    foreach ($list as $item) {
      $widget_instance_id = isset($item['widget_instance_id']) ? $item['widget_instance_id'] : FALSE;
      if ($widget_instance_id) {

        // Widget instance exists.
        $widget_instance = WidgetInstanceEntity::load($widget_instance_id);
        if ($widget_instance->getIsRequired()) {

          // This is a required widget instance on this node.
          // Add it to the array.
          $required_type = $widget_instance->getRequiredType();
          $required_bundle = $widget_instance->getRequiredBundle();
          $required_on_node[$required_type][$required_bundle] = $widget_instance_id;

        }

      }
    }

    // Add any required stacks to the list. Handle both types of required
    // widgets.
    $this->addRequiredFields($node, 'open', $required_on_node);
    $this->addRequiredFields($node, 'locked', $required_on_node);
  }

  /**
   * This is where we add the required widgest on the node that are not there.
   *
   * @param $node
   * @param $required_on_node
   */
  private function addRequiredFields(&$node, $required_type, $required_on_node) {
    // Grab the latest items. We need to do this because this can change each
    // time this method is called!
    $list = $node->get($this->field_name)->getValue();

    $settings = $this->settings_required;
    if ($required_type == 'open') {
      $settings = $this->settings_required_loc_optional;
    }

    // Now compare the required stacks with the data they have on this node.
    // If a required widget needs to be added, add it to the beginning of
    // the list.
    $changed = FALSE;
    foreach ($settings as $required_bundle) {
      if (!empty($required_bundle) && !isset($required_on_node[$required_type][$required_bundle])) {
        // They do not have this required widget on the node. We need to add it.
        // Create Required Widget entity.
        $widget_entity = WidgetEntity::create([
          'type' => $required_bundle,
        ]);

        $widget_entity->save();

        // Create Required Widget Instance.
        $widget_instance_entity = WidgetInstanceEntity::create([
          'type' => 'widget_instance_entity',
          'title' => $required_bundle . ': ' . t('Required Type') . ' (' . $required_type . ')',
          'enable_sharing' => FALSE,
          'widget_entity' => $widget_entity->id(),
          'required' => TRUE,
          'required_type' => $required_type,
          'required_bundle' => $required_bundle,
        ]);

        $widget_instance_entity->save();

        // Add the item to the beginning of the list.
        $add_required_bundle = [
          'widget_instance_id' => $widget_instance_entity->id(),
          'required' => TRUE,
          'required_type' => $required_type,
          'required_bundle' => $required_bundle,
        ];

        array_unshift($list, $add_required_bundle);
        $changed = TRUE;
      }
    }

    // If the list has changed, make sure to update the value.
    if ($changed) {
      $node->get($this->field_name)->setValue($list);
    }
  }

}
