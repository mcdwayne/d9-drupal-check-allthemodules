<?php

namespace Drupal\select2boxes;

use Drupal\Core\Entity\EntityInterface;

/**
 * Trait AutoCreationProcessTrait.
 *
 * @package Drupal\select2boxes
 */
trait AutoCreationProcessTrait {
  use EntityCreationTrait;

  /**
   * Process the auto-creations and then normalise the input.
   *
   * @param array $element
   *   The element.
   * @param mixed $input
   *   The submitted data for the element.
   *
   * @return mixed
   *   The processed input.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function processAutoCreation(array &$element, $input) {
    $data = $element['#select2'];
    if (!(isset($data['handler_settings']['auto_create']) && $data['handler_settings']['auto_create']) || !$input) {
      // Don't return false even though we were given it... That is bad somehow.
      return $input !== FALSE ? $input : ($element['#multiple'] ? [] : NULL);
    }

    // Prepare the data.
    $output        = NULL;
    $options       = $element['#options'];
    $target_type   = $data['target_type'];
    $definition    = \Drupal::entityTypeManager()->getDefinition($target_type);
    $target_bundle = !empty($data['handler_settings']['auto_create_bundle'])
      ? $data['handler_settings']['auto_create_bundle']
      : reset($data['handler_settings']['target_bundles']);

    // Prepare entity metadata array.
    $entity_metadata = [
      $target_type,
      $target_bundle,
      $definition,
    ];

    // Handle multi-value widget.
    if ($element['#multiple']) {
      // Process each item.
      foreach ($input as $item) {
        $output[] = self::processValueItem($entity_metadata, $item, $options);
      }
    }
    else {
      // Handle single-value widget.
      $output = self::processValueItem($entity_metadata, $input, $options);
    }

    $element['#options'] = $options;

    return $output;
  }

  /**
   * Process value item.
   *
   * @param array $entity_metadata
   *   Entity metadata array:
   *   array(entity_type_id, entity_bundle, entity_type_definitions).
   * @param mixed $item
   *   Item value.
   * @param array &$options
   *   Options array passed by reference.
   *
   * @return int|null|string
   *   Entity ID of precessed value item.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private static function processValueItem(array $entity_metadata, $item, array &$options) {
    list($type, $bundle, $definition) = $entity_metadata;
    $entity = static::getEntity($type, $item);
    if (!$entity instanceof EntityInterface) {
      // Get or create entity (ensuring no accidental duplicates).
      $entity = static::getOrCreateEntity($type, [
        $definition->getKey('label')  => $item,
        $definition->getKey('bundle') => $bundle,
      ]);
    }
    $options += [$entity->id() => $entity->label()];
    return $entity->id();
  }

}
