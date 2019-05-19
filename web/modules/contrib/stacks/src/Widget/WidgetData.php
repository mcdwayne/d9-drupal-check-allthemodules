<?php

namespace Drupal\stacks\Widget;

use Drupal;
use Drupal\stacks\Entity\WidgetEntityType;
use Drupal\stacks\Entity\WidgetInstanceEntity;
use Drupal\stacks\Entity\WidgetEntity;
use Drupal\stacks\Grid\ContentFeed\NodeContent;
use Drupal\stacks\Ajax\EditWidgetCommand;

/**
 * Class WidgetData.
 */
class WidgetData {

  /**
   * Main router for returning the render arrays for the Stacks system.
   *
   * @param $entity : The entity that is being loaded.
   * @param $widget_entity : The stacks entity being rendered.
   * @param $widget_region : Region where this widget will be rendered. It's a reference
   *   and must be "returned" as such (generated inside this function). Default
   *   is "content".
   * @return array
   */
  public function output($entity, WidgetInstanceEntity $widget_instance_entity, WidgetEntity $widget_entity, $widget_region = 'content', $field_properties = []) {

    $result = Drupal::moduleHandler()->invokeAll('stacks_pre_output', array(
      $entity,
      $widget_instance_entity,
      $widget_entity,
    ));

    if (!empty($result['skip'])) {
      return [];
    }

    $entity_id = $entity->id();
    $field_exceptions = [];

    // If this is a custom widget type, grab the widget type object.
    $widget_type_object = $this->getWidgetTypeObject($widget_entity);
    if (is_object($widget_type_object) && method_exists($widget_type_object, 'fieldExceptions')) {
      $field_exceptions = $widget_type_object->fieldExceptions();
    }

    // Get the values for all fields on this stacks entity.
    $data = [];
    $rendered_fields = [];
    foreach ($widget_entity->getFields() as $key => &$field) {
      $this->fieldValues($key, $field, $data, $field_exceptions);
      $rendered_fields[$key] = $widget_entity->$key->view('default');
    }

    // Put together the render array. See stacks_theme() for descriptions
    // of the variables.
    $render_array = [
      '#theme' => $widget_entity->bundle() . '__' . $widget_instance_entity->getTemplate(),
      '#content_entity' => [
        'entity_id' => $entity_id,
        'entity_type' => $entity->getEntityTypeId(),
        'entity_bundle' => $entity->bundle(),
      ],
      '#widget_entity' => [
        'entity_id' => $widget_entity->id(),
        'entity_type' => $widget_entity->getEntityTypeId(),
        'entity_bundle' => $widget_entity->bundle(),
      ],
      '#fields' => $data,
      '#rendered_fields' => $rendered_fields,
      '#wrapper_id' => $widget_instance_entity->getWrapperID(),
      '#wrapper_classes' => $widget_instance_entity->getWrapperClasses(),
      '#template_theme' => $widget_instance_entity->getTheme(),
    ];

    // Add contextual links.
    $user = Drupal::currentUser();

    if (\Drupal::moduleHandler()->moduleExists('contextual') &&
      $user->hasPermission('add stacks entity entities')) {

      $field_name = '';
      $delta = '';

      // TODO: check other instances of the output() method to add the field properties
      if (isset($field_properties)) {
        $field_name = $field_properties['field_name'];
        $delta = $field_properties['delta'];
      }

      $render_array['#content_entity']['#contextual_links'] = [
        'stacks' => [
          'route_parameters' => [
            'nid' => $entity_id,
            'id' => $widget_entity->id(),
            'field_name' => $field_name,
            'delta' => $delta,
          ],
        ]
      ];

      $render_array['#wrapper_classes'] .= ' contextual-region';
      $render_array['#content_entity']['#attributes']['class'][] = 'custom-widget-class';
    }

    if (substr($widget_entity->bundle(), 0, 11) === 'contentfeed') {
      $render_array['#cache'] = [
        'max-age' => 300,
        'contexts' => ['user.roles:anonymous'],
      ];
    }

    // Allow the render array to be modified.
    if ($widget_type_object && method_exists($widget_type_object, 'modifyRenderArray')) {
      // For grid stacks only.
      $widget_type_object->modifyRenderArray($render_array);
    }
    else {
      // Call hook_stacks_output_alter() for none grid widgets.
      Drupal::moduleHandler()
        ->alter('stacks_output', $render_array, $entity, $widget_entity);
    }

    if ($user->hasPermission('add stacks entity entities')) {
      $render_array['#attached']['library'][] = 'stacks/admin_widget_editor';
    }

    return $render_array;
  }

  /**
   * Returns the correct widget type object, based on grid type (bundle).
   * Or False if there is no widget type object.
   */
  static function getWidgetTypeObject(WidgetEntity $widget_entity) {
    $widget_type_manager = \Drupal::service('plugin.manager.stacks_widget_type');
    $bundle = $widget_entity->bundle();

    $widget_entity_type = WidgetEntityType::load($bundle);
    $plugin = $widget_entity_type->getPlugin();

    if (!$widget_type_manager->hasDefinition($plugin)) {
      return FALSE;
    }

    return $widget_type_manager->createInstance($plugin, ['widget_entity' => $widget_entity]);
  }

  /**
   * Handles the array for the value. Since a field can have one or more values,
   * we need to handle each scenario.
   *
   * @param $key : Key of the field.
   * @param $field : Field variable array.
   * @param $data : The persistent data variable.
   */
  public function fieldValues($key, $field, &$data, $field_exceptions = []) {
    // Ignore certain properties or fields on the entity.
    $field_exceptions = array_merge(WidgetData::fieldExceptionsDefault(), $field_exceptions);
    if (in_array($key, $field_exceptions)) {
      return;
    }

    // If the field doesn't have any values, set to false for the template.
    if ($field->isEmpty()) {
      $data[$key] = FALSE;
      return;
    }

    // Set variables from the field.
    $field_type = $field->getFieldDefinition()->getType();
    $value = $field->getValue();
    $cardinality = $field->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getCardinality();

    $delta = 0;
    if ($cardinality == -1 || $cardinality > 1) {
      // There are multiple values for this field...create an array of all values.
      $data[$key] = [];
      foreach ($value as $cur_value) {
        $data[$key][] = $this->fieldValue($field_type, $field, $value[$delta], $delta);
        $delta++;
      }
    }
    else {
      // There is only one value for this field.
      $data[$key] = $this->fieldValue($field_type, $field, $value[$delta], $delta);
    }
  }

  /**
   * Returns a list of fields/properties that we don't want to return the data
   * for, by default.
   */
  static public function fieldExceptionsDefault() {
    return [
      'title',
      'uid',
      'id',
      'uuid',
      'type',
      'langcode',
      'default_langcode',
      'created',
      'changed',
      'widget_times_used',
      'status',
      'revision_timestamp',
      'preferred_langcode',
      'pass',
      'access',
      'login',
      'revision_uid',
    ];
  }

  /**
   * Returns render array based field type. Also processes tokens.
   *
   * @param $field_type
   * @param $field
   * @param $cur_value
   * @param $delta
   * @return mixed
   */
  private function fieldValue($field_type, $field, $cur_value, $delta) {
    $field_handler = new WidgetFieldHandlers($field_type, $field, $cur_value, $delta);
    $render_array = $field_handler->getRenderArray();

    if (isset($render_array['#markup'])) {
      $token_service = Drupal::service('token');
      $render_array['#markup'] = $token_service->replace($render_array['#markup']);
    }

    return $render_array;
  }

  /**
   * Takes a widget instance entity, and returns the label of the widget bundle
   * that is connected to that widget instance.
   *
   * @param \Drupal\stacks\Entity\WidgetInstanceEntity $widget_instance
   * @return string
   */
  static public function getWidgetType(WidgetInstanceEntity $widget_instance) {
    $bundles_get = \Drupal::entityManager()->getBundleInfo('widget_entity');
    $widget_entity = $widget_instance->getWidgetEntity();
    $widget_bundle = $widget_entity->bundle();
    return isset($bundles_get[$widget_bundle]['label']) ? $bundles_get[$widget_bundle]['label'] : '';
  }

  /**
   * Get the field name holding IEF on content list types
   */
  static public function getIEFFieldName($widget_bundle) {
    $fields = \Drupal::entityManager()
      ->getFieldDefinitions('widget_entity', $widget_bundle);

    $found_field_name = "";
    foreach ($fields as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
        if ($field_definition->getType() === 'entity_reference') {
          $settings = $field_definition->getSettings();
          if (isset($settings['target_type']) && $settings['target_type'] === 'widget_extend') {
            $found_field_name = $field_name;
            break;
          }
        }
      }
    }

    return $found_field_name;
  }

  /**
   * Get the field name holding IEF on content list types
   */
  static public function getIEFFieldTabBundles($widget_bundle) {
    $fields = \Drupal::entityManager()
      ->getFieldDefinitions('widget_entity', $widget_bundle);

    $found_bundles = [];
    foreach ($fields as $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
        if ($field_definition->getType() === 'entity_reference') {
          $settings = $field_definition->getSettings();
          if (isset($settings['target_type']) && $settings['target_type'] === 'widget_extend') {
            $hs = $field_definition->getSetting('handler_settings');
            if (isset($hs['target_bundles']) && is_array($hs['target_bundles'])) {
              $found_bundles = $hs['target_bundles'];
              break;
            }
          }
        }
      }
    }

    $ret = [];
    foreach ($found_bundles as $key => $value) {
      $ret[$key] = WidgetData::getWidgetExtendType_fromwidget($key);
    }

    return $ret;
  }

  /**
   * Similar to getWidgetType(), except we take a stacks entity bundle machine
   * name and get the label from that.
   */
  static public function getWidgetExtendType_fromwidget($widget_bundle) {
    $bundles_get = \Drupal::entityManager()->getBundleInfo('widget_extend');
    return isset($bundles_get[$widget_bundle]['label']) ? $bundles_get[$widget_bundle]['label'] : '';
  }

  /**
   * Similar to getWidgetType(), except we take a stacks entity bundle machine
   * name and get the label from that.
   */
  static public function getWidgetType_fromwidget($widget_bundle) {
    $bundles_get = \Drupal::entityManager()->getBundleInfo('widget_entity');
    return isset($bundles_get[$widget_bundle]['label']) ? $bundles_get[$widget_bundle]['label'] : '';
  }

}
