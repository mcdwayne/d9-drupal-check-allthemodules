<?php

namespace Drupal\flexiform\Plugin\Deriver;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\ctools\Plugin\Deriver\TypedDataPropertyDeriverBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a deriver class for Flexiform form entities.
 */
class FormEntityTypedDataReferencedEntityDeriver extends TypedDataPropertyDeriverBase {

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityBundleInfo;

  /**
   * FormEntityTypedDataReferencedEntityDeriver constructor.
   *
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data_manager
   *   The typed data manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_bundle_info
   *   The entity type manager service.
   */
  public function __construct(TypedDataManagerInterface $typed_data_manager, TranslationInterface $string_translation, EntityTypeBundleInfoInterface $entity_bundle_info) {
    parent::__construct($typed_data_manager, $string_translation);
    $this->entityBundleInfo = $entity_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('typed_data_manager'),
      $container->get('string_translation'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function generateDerivativeDefinition($base_plugin_definition, $data_type_id, $data_type_definition, DataDefinitionInterface $base_definition, $property_name, DataDefinitionInterface $property_definition) {
    // Only add derivatives on entity reference fields.
    if (!method_exists($property_definition, 'getType') || $property_definition->getType() != 'entity_reference') {
      return;
    }

    /* @var \Drupal\Core\Field\FieldDefinitionInterface $property_definition */
    $bundle_info = $base_definition->getConstraint('Bundle');
    // Identify base definitions that appear on bundle-able entities.
    if ($bundle_info && array_filter($bundle_info) && $base_definition->getConstraint('EntityType')) {
      $base_data_type = 'entity:' . $base_definition->getConstraint('EntityType');
    }
    // Otherwise, just use the raw data type identifier.
    else {
      $base_data_type = $data_type_id;
    }

    // If we've not processed this thing before.
    if (!isset($this->derivatives[$base_data_type . ':' . $property_name])) {
      $derivative = $base_plugin_definition;

      $derivative['label'] = $this->t('@property Entity from @base', [
        '@property' => $property_definition->getLabel(),
        '@base' => $data_type_definition['label'],
      ]);

      $context_definition = new ContextDefinition($base_data_type, $data_type_definition['label'], TRUE);
      // Add the constraints of the base definition to the context definition.
      if ($base_definition->getConstraint('Bundle')) {
        $context_definition->addConstraint('Bundle', $base_definition->getConstraint('Bundle'));
      }
      $derivative['context'] = [
        'base' => $context_definition,
      ];
      $derivative['property_name'] = $property_name;

      $derivative['entity_type'] = $property_definition->getFieldStorageDefinition()->getPropertyDefinition('entity')->getConstraint('EntityType');
      if ($bundle = $property_definition->getFieldStorageDefinition()->getPropertyDefinition('entity')->getConstraint('Bundle')) {
        $derivative['bundle'] = $bundle;
        $this->derivatives[$base_data_type . ':' . $property_name . ':' . $bundle] = $derivative;
      }
      else {
        $label = $derivative['label'];
        foreach ($this->entityBundleInfo->getBundleInfo($derivative['entity_type']) as $bundle => $info) {
          $derivative['label'] = $label . ' (' . $info['label'] . ')';
          $derivative['bundle'] = $bundle;
          $this->derivatives[$base_data_type . ':' . $property_name . ':' . $bundle] = $derivative;
        }
      }
    }
  }

}
