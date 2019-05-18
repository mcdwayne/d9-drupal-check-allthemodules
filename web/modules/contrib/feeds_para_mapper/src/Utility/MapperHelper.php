<?php
namespace Drupal\feeds_para_mapper\Utility;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\field\FieldConfigInterface;

trait MapperHelper
{
  /**
   * @return FieldConfigInterface[]
   */
  public static function getTargets($entityType, $bundle, $with_path = TRUE)
  {
    $mapper = new static();
    $paragraphs_fields = $mapper->findParagraphsFields($entityType,$bundle);
    $fields = array();
    foreach ($paragraphs_fields as $paragraphs_field) {
      $fields += self::getSubFields($paragraphs_field, $with_path);
    }
    // Remove the fields that don't support feeds:
    $targets_manager = \Drupal::service('plugin.manager.feeds.target');
    $definitions = $targets_manager->getDefinitions();
    $supported = array_keys($definitions);
    $fields = array_filter($fields, function ($item) use ($supported) {
      $type = $item->getType();
      return in_array($type, $supported);
    });
    // Add some info the field info object:
    $prepared = array();
    foreach ($fields as $field) {
      foreach ($definitions as $name => $plugin) {
        if($name === $field->getType()){
          self::updateInfo($field, "plugin", $plugin);
          self::updateInfo($field, "class", $plugin['class']);
          self::updateInfo($field, "type", $field->getType());
          $prepared[] = $field;
        }
      }
    }
    return $prepared;
  }
  public function findParagraphsFields($entity_type, $bundle){
    $fieldManager = \Drupal::service('entity_field.manager');
    $entityFields = $fieldManager->getFieldDefinitions($entity_type,$bundle);
    $entityFields = array_filter($entityFields, function ($item){
      return $item instanceof FieldConfigInterface;
    });
    $fields = array();
    foreach ($entityFields as $field) {
      if($field->getType() === 'entity_reference_revisions') {
        $fields[] = $field;
      }
    }
    return $fields;
  }
  public static function getSubFields(FieldConfigInterface $target, $with_path = FALSE, array $result = array(), array $first_host = array()){
    $settings = $target->getSettings();
    $target_bundles = $settings['handler_settings']['target_bundles'];
    $target_bundles = array_values($target_bundles);
    foreach ($target_bundles as $target_bundle) {
      $entityManager = \Drupal::service('entity_field.manager');
      $sub_fields = $entityManager->getFieldDefinitions('paragraph', $target_bundle);
      $sub_fields = array_filter($sub_fields, function ($item){
        return $item instanceof FieldConfigInterface;
      });
      foreach ($sub_fields as $machine_name => $sub_field) {
        // Initialize first host:
        if ($target->getTargetEntityTypeId() !== 'paragraph') {
          $first_host = array(
            'bundle' => $target_bundle,
            'host_field' => $target->getName(),
            'host_entity' => $target->getTargetEntityTypeId(),
          );
        }
        // If we found nested Paragraphs field,
        // loop through its sub fields to include them:
        if ($sub_field->getType() === 'entity_reference_revisions') {
          $result = self::getSubFields($sub_field, $with_path, $result, $first_host);
        }
        else if($sub_field->getType() !== "feeds_item"){
          $host_allowed = $target->getFieldStorageDefinition()->getCardinality();
          $fieldAllowed = $sub_field->getFieldStorageDefinition()->getCardinality();
          $unlimited = FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED;
          $has_settings = FALSE;
          if ($host_allowed === $unlimited || $host_allowed > 1) {
            if ($fieldAllowed === $unlimited || $fieldAllowed > 1) {
              $has_settings = TRUE;
            }
          }
          self::updateInfo($sub_field, "has_settings", $has_settings);
          // @todo: continue here
          self::setFieldsInCommon($sub_field, $result);
          if ($with_path) {
            $path = self::buildPath($sub_field, $first_host);
            self::updateInfo($sub_field, "path", $path);
          }
          $result[] = $sub_field;
        }
      }
    }
    return $result;
  }

  public static function buildPath(FieldConfigInterface $field, array $first_host) {
    $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo('paragraph');
    // Get bundles fields:
    foreach ($bundles as $name => $bundle) {
      $bundles[$name]['name'] = $name;
      $fields = \Drupal::getContainer()->get('entity_field.manager')->getFieldDefinitions('paragraph', $name);
      $fields = array_filter($fields, function ($item){
        return $item instanceof FieldConfigInterface;
      });
      $bundles[$name]['fields'] = $fields;
    }
    $field_bundle = NULL;
    $getFieldBundle = function (FieldConfigInterface $field) use ($bundles) {
      foreach ($bundles as $bundle) {
        foreach ($bundle['fields'] as $b_field) {
          if ($b_field->getName() === $field->getName()) {
            return $bundle;
          }
        }
      }
      return NULL;
    };
    $getHost = function ($field_bundle) use ($bundles) {
      foreach ($bundles as $bundle) {
        foreach ($bundle['fields'] as $b_field) {
          $settings = $b_field->getSetting('handler_settings');
          if (isset($settings['target_bundles'])) {
            foreach ($settings['target_bundles'] as $allowed_bundle) {
              if ($allowed_bundle === $field_bundle['name']) {
                /*
                Get the allowed bundle and set it as the host bundle.
                This grabs the first allowed bundle,
                and might cause issues with multiple bundles field.
                todo: Test with multiple bundles field.
                 */
                $allowed = array_filter($bundles, function ($item) use ($allowed_bundle) {
                  return $item['name'] === $allowed_bundle;
                });
                $allowed = array_values($allowed);
                return array(
                  'bundle' => $allowed[0],
                  'host_field' => $b_field,
                );
              }
            }
          }
        }
      }
      return NULL;
    };
    // Start building the path:
    $path = array();
    $field_bundle = $getFieldBundle($field);
    while (isset($field_bundle)) {
      $host = $getHost($field_bundle);
      if (isset($host)) {
        $new_path = array(
          'bundle' => $host['bundle']['name'],
          'host_field' => $host['host_field']->getName(),
          'host_entity' => 'paragraph',
        );
        array_unshift($path, $new_path);
        $field_bundle = $getFieldBundle($host['host_field']);
      }
      else {
        $field_bundle = NULL;
      }
    }
    // Add the first host to the path:
    array_unshift($path, $first_host);
    // Add order to all path items:
    for ($i = 0; $i < count($path); $i++) {
      $path[$i]['order'] = $i;
    }
    return $path;
  }

  /**
   * Finds fields that share the same host as the target.
   *
   * @param FieldConfigInterface $field
   *   The target fields.
   * @param array $fields
   *   The other collected fields so far.
   */
  public static function setFieldsInCommon(FieldConfigInterface &$field, array &$fields) {
    foreach ($fields as $key => $other_field) {
      $other_info = $other_field->get('target_info');
      $last_key = count($other_info->path) - 1;
      $others_host = $other_info->path[$last_key];
      $info = $field->get('target_info');
      $current_host_key = count($info->path) - 1;
      $current_host = $info->path[$current_host_key];
      if ($others_host['host_field'] === $current_host['host_field']) {
        if (!isset($info->in_common)) {
          $info->in_common = array();
        }
        if (!isset($other_info->in_common)) {
          $other_info->in_common = array();
        }
        $other_field_in_common = array(
          'id' => $other_field->id(),
          'name' => $other_field->getName()
        );
        $field_in_common = array(
          'id' => $field->id(),
          'name' => $field->getName()
        );
        $info->in_common[] = $other_field_in_common;
        $field->set('target_info', $info);
        $other_info->in_common[] = $field_in_common;
        $other_field->set('target_info', $other_info);
      }
    }
  }

  /**
   * Updates the target field info.
   *
   * @param FieldConfigInterface $field
   * @param $property
   * @param $value
   */
  public static function updateInfo(FieldConfigInterface $field, $property, $value){
    $info = $field->get('target_info');
    if(!isset($info)){
      $info = new TargetInfo();
    }
    $info->{$property} = $value;
    $field->set('target_info', $info);
  }


  /**
   * @param mixed $targetDefinition
   * @param string $property
   * @return mixed
   */
  public static function getInfo($targetDefinition, $property){
    $field = $targetDefinition;
    if($targetDefinition instanceof FieldTargetDefinition){
      $field = $targetDefinition->getFieldDefinition();
    }
    $info = $field->get('target_info');
    return $info->{$property};
  }

  /**
   * Gets the maximum values for a field.
   *
   * Gets the maximum values a field can hold,
   * or the user choice of the maximum values.
   *
   * @param FieldConfigInterface $target
   *   The target field.
   *
   * @return int
   *   The maximum values
   */
  public static function getMaxValues(FieldConfigInterface $target, $configuration = null) {
    $res = NULL;
    $cardinality = (int) $target->getFieldStorageDefinition()->getCardinality();
    if (isset($configuration['max_values'])) {
      $unlimited = $cardinality === -1;
      $max_values = (int) $configuration['max_values'];
      $valid = $max_values >= -1 && $max_values !== 0;
      if ($max_values <= $cardinality && $valid || ($unlimited && -1 <= $max_values && $valid)) {
        $res = $max_values;
      }
      else {
        $res = $cardinality;
      }
    }
    else {
      $res = $cardinality;
    }
    return $res;
  }
}