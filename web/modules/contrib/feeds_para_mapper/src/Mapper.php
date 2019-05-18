<?php

namespace Drupal\feeds_para_mapper;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\feeds\Plugin\Type\FeedsPluginManager;
use Drupal\feeds_para_mapper\Utility\TargetInfo;
use Drupal\field\FieldConfigInterface;

class Mapper
{

  /**
   * @var FeedsPluginManager
   */
  protected $targetsManager;

  /**
   * @var EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * @var EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Mapper constructor.
   * @param FeedsPluginManager $targetsManager
   * @param EntityFieldManagerInterface $entityFieldManager
   * @param EntityTypeBundleInfoInterface $bundleInfo
   */
  public function __construct(FeedsPluginManager $targetsManager, EntityFieldManagerInterface $entityFieldManager, EntityTypeBundleInfoInterface $bundleInfo)
  {
    $this->targetsManager     = $targetsManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->bundleInfo         = $bundleInfo;
  }

  /**
   * @param string $entityType
   * @param string $bundle
   *
   * @return FieldConfigInterface[]
   */
  public function getTargets($entityType, $bundle)
  {
    $paragraphs_fields = $this->findParagraphsFields($entityType, $bundle);
    $fields = array();
    // Get sub fields for each paragraph field:
    foreach ($paragraphs_fields as $paragraphs_field) {
      $subFields = $this->getSubFields($paragraphs_field);
      $fields = array_merge($fields, $subFields);
    }
    // Remove the fields that don't support feeds,
    // and add some info on the supported fields:
    $definitions = $this->targetsManager->getDefinitions();
    $supported = array();
    foreach ($fields as $field) {
      foreach ($definitions as $name => $plugin ) {
        if(in_array($field->getType(), $plugin['field_types'])) {
          $this->updateInfo($field, "plugin", $plugin);
          $this->updateInfo($field, "type", $field->getType());
          $supported[] = $field;
          break;
        }
      }
    }
    return $supported;
  }

  /**
   * @param string $entity_type
   * @param string $bundle
   * @return FieldDefinitionInterface[]
   */
  public function findParagraphsFields($entity_type, $bundle){
    $fields = array();
    $entityFields = $this->entityFieldManager->getFieldDefinitions($entity_type,$bundle);
    if(!isset($entityFields)){
      return $fields;
    }
    $entityFields = array_filter($entityFields, function ($item){
      return $item instanceof FieldConfigInterface;
    });
    foreach ($entityFields as $field) {
      if($field->getType() === 'entity_reference_revisions') {
        $fields[] = $field;
      }
    }
    return $fields;
  }

  /**
   * @param FieldDefinitionInterface $target
   * @param array $result
   * @param array $first_host
   * @return array
   */
  public function getSubFields($target, array $result = array(), array $first_host = array()){
    $target_bundles = $this->getEnabledBundles($target);
    foreach ($target_bundles as $target_bundle) {
      $sub_fields = $this->entityFieldManager->getFieldDefinitions('paragraph', $target_bundle);
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
            'host_field_bundle' => $target->get('bundle'),
          );
        }
        // If we found nested Paragraphs field,
        // loop through its sub fields to include them:
        $wrapped = isset($sub_field->target_info);
        if ($sub_field->getType() === 'entity_reference_revisions' && !$wrapped) {
          $result = $this->getSubFields($sub_field, $result, $first_host);
        }
        else if($sub_field->getType() !== "feeds_item" && !$wrapped){
          $host_allowed = $target->getFieldStorageDefinition()->getCardinality();
          $fieldAllowed = $sub_field->getFieldStorageDefinition()->getCardinality();
          $unlimited = FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED;
          $has_settings = FALSE;
          if ($host_allowed === $unlimited || $host_allowed > 1) {
            if ($fieldAllowed === $unlimited || $fieldAllowed > 1) {
              $has_settings = TRUE;
            }
          }
          $this->updateInfo($sub_field, "has_settings", $has_settings);
          $path = $this->buildPath($sub_field, $first_host);
          $this->updateInfo($sub_field, "path", $path);
          $this->setFieldsInCommon($sub_field, $result);
          $result[] = $sub_field;
        }
        else if ($wrapped) {
          $type = $sub_field->target_info->type;
          $sub_field->set('field_type', $type);
          $result[] = $sub_field;
        }
      }
    }
    return $result;
  }

  /**
   * Gets the enabled bundles for a paragraph field.
   *
   * @param FieldDefinitionInterface $target
   *
   * @return array
   */
  private function getEnabledBundles($target){
    $settings = $target->getSettings();
    $target_bundles = array();
    if (isset($settings['handler_settings']['target_bundles'])) {
      $target_bundles = $settings['handler_settings']['target_bundles'];
      $target_bundles = array_values($target_bundles);
    }
    else if (isset($settings['handler_settings']['target_bundles_drag_drop'])) {
      // get the selected bundles:
      $selected_bundles = array_filter($settings['handler_settings']['target_bundles_drag_drop'], function($item){
        return $item['enabled'];
      });

      if (count($selected_bundles)){
        $target_bundles = array_keys($selected_bundles);
      }
      else {
        // no selected bundles, return all bundles:
        $target_bundles = array_keys($settings['handler_settings']['target_bundles_drag_drop']);
      }
    }

    // Remove the $target bundle to prevent looping:
    $target_bundle = $target->get('bundle');
    $target_bundles = array_filter($target_bundles, function($item) use ($target_bundle){
      return $item !== $target_bundle;
    });
    return $target_bundles;
  }
  /**
   * Updates a property of TargetInfo object.
   *
   * @param FieldDefinitionInterface $field
   * @param $property
   * @param $value
   *
   * @return bool
   *   true on success.
   */
  public function updateInfo(FieldDefinitionInterface $field, $property, $value){
    $info = $field->get('target_info');
    if(!isset($info)){
      $info = new TargetInfo();
    }
    $res = false;
    if( property_exists(TargetInfo::class, $property)){
      $info->{$property} = $value;
      $field->set('target_info', $info);
      $res = true;
    }
    return $res;
  }

  /**
   * @param FieldDefinitionInterface $field
   * @param string $property
   * @return mixed
   */
  public function getInfo(FieldDefinitionInterface $field, $property){
    $info = $field->get('target_info');
    if(!isset($info)){
      $info = new TargetInfo();
    }
    $res = null;
    if(property_exists(TargetInfo::class, $property)){
      $res = $info->{$property};
    }
    return $res;
  }

  /**
   * Finds fields that share the same host as the target.
   *
   * @param FieldDefinitionInterface $field
   *   The target fields.
   * @param FieldDefinitionInterface[] $fields
   *   The other collected fields so far.
   */
  private function setFieldsInCommon(FieldDefinitionInterface &$field, array &$fields) {
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
   * Gets the field path (in case its nested)
   * @param FieldDefinitionInterface $field
   * @param array $first_host
   * @return array
   */
  private function buildPath(FieldDefinitionInterface $field, array $first_host) {
    $bundles = $this->bundleInfo->getBundleInfo('paragraph');
    // Get bundles fields:
    foreach ($bundles as $name => $bundle) {
      $bundles[$name]['name'] = $name;
      $fields = $this->entityFieldManager->getFieldDefinitions('paragraph', $name);
      $fields = array_filter($fields, function ($item){
        return $item instanceof FieldConfigInterface;
      });
      $bundles[$name]['fields'] = $fields;
    }
    $field_bundle = NULL;
    $getFieldBundle = function (FieldDefinitionInterface $field) use ($bundles) {
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
          'host_field_bundle' => $host['host_field']->get('bundle'),
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
   * Gets the maximum values for a field.
   *
   * Gets the maximum values a field can hold,
   * or the user choice of the maximum values.
   *
   * @param FieldDefinitionInterface $target
   *   The target field.
   * @param array $configuration
   *   The mapping configuration.
   *
   * @return int
   *   The maximum values
   */
  public function getMaxValues(FieldDefinitionInterface $target, array $configuration = null) {
    $crd = (int) $target->getFieldStorageDefinition()->getCardinality();
    if(!isset($configuration['max_values'])){
      return $crd;
    }
    $unlimited = $crd === -1;
    $max_values = (int) $configuration['max_values'];
    $valid = $max_values <= $crd && !$unlimited && !($max_values < 0 && $crd > 0) || $unlimited && $max_values >= -1;
    if ($valid){
      $res = $max_values;
    }
    else {
      $res = $crd;
    }
    return $res;
  }

  /**
   * Loads the parent fields for a nested field
   *
   * @param $field
   *
   * @return FieldDefinitionInterface[]
   */
  public function loadParentFields(FieldDefinitionInterface $field){
    $target_info = $field->target_info;
    $parents = array();
    foreach ($target_info->path as $parentI) {
      $bundle = isset($parentI['host_field_bundle']) ? $parentI['host_field_bundle']: $parentI['bundle'];
      $fields = $this->entityFieldManager->getFieldDefinitions($parentI['host_entity'],$bundle);
      $parents[] = $fields[$parentI['host_field']];
    }
    return $parents;
  }
}