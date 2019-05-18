<?php

namespace Drupal\reference_map\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ReferenceMapMap constraint.
 */
class ReferenceMapMapValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Entity Type Bundle Info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The Entity Field Manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs the object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle information service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($reference_map_config, Constraint $constraint) {
    $map = $reference_map_config->map;

    // Ensure the map is an array.
    if (!is_array($map)) {
      $this->context->buildViolation($constraint->mapNotArray)
        ->atPath('map')
        ->addViolation();

      return;
    }

    // Ensure the map has a minimum of two steps.
    if (count($map) < 2) {
      $this->context->buildViolation($constraint->mapMinimumSteps)
        ->atPath('map')
        ->addViolation();
    }

    $last_index = count($map);
    foreach ($map as $index => $step) {
      $index++;

      // Ensure the step is an array.
      if (!is_array($step)) {
        $this->context->buildViolation($constraint->stepNotArray, [
          '%position' => $index,
        ])
          ->atPath('map')
          ->addViolation();

        continue;
      }

      // Ensure the step has an entity_type key.
      if (empty($step['entity_type'])) {
        $this->context->buildViolation($constraint->stepMissingKey, [
          '%position' => $index,
          '%key' => 'entity_type',
        ])
          ->atPath('map')
          ->addViolation();

        continue;
      }

      // Ensure the entity type is a valid content entity type.
      $entity_type = $this->entityTypeManager->getDefinition($step['entity_type'], FALSE);
      if (!$entity_type) {
        $this->context->buildViolation($constraint->stepInvalidEntityType, [
          '%position' => $index,
        ])
          ->atPath('map')
          ->addViolation();

        continue;
      }
      $entity_type_id = $entity_type->id();

      // If bundles were specified for this step.
      if (!empty($step['bundles'])) {
        // Ensure the bundles key is an array.
        if (!is_array($step['bundles'])) {
          $this->context->buildViolation($constraint->stepBundlesNotArray, [
            '%position' => $index,
          ])
            ->atPath('map')
            ->addViolation();

          continue;
        }

        // Get the bundles for the entity type.
        $bundles = array_keys($this->entityTypeBundleInfo->getBundleInfo($entity_type_id));
        // Ensure the steps bundles are valid for this entity type.
        $bundles = array_diff($step['bundles'], $bundles);
        if (!empty($bundles)) {
          // Convert the list of bundles into a comma/and separated string.
          $bundles_string = [implode(', ', array_slice($bundles, 0, -1))];
          $bundles_string = implode(' ' . $this->t('and') . ' ', array_filter(array_merge($bundles_string, array_slice($bundles, -1)), 'strlen'));
          $this->context->buildViolation($constraint->stepInvalidBundles)
            ->atPath('map')
            ->setParameter('%bundles', $bundles_string)
            ->setParameter('%position', $index)
            ->setPlural(count($bundles))
            ->addViolation();

          continue;
        }
      }

      // If we're on the last step.
      if ($index === $last_index) {
        // Ensure a field_name key doesn't exist.
        if (!empty($step['field_name'])) {
          $this->context->buildViolation($constraint->stepLastHasFieldName)
            ->atPath('map')
            ->addViolation();
        }
      }
      // If we're not on the last step, run field validations.
      else {
        // Ensure steps other than the last have a field_name key.
        if (empty($step['field_name'])) {
          $this->context->buildViolation($constraint->stepMissingKey, [
            '%position' => $index,
            '%key' => 'field_name',
          ])
            ->atPath('map')
            ->addViolation();

          continue;
        }

        // Ensure the field applies to the entity type and bundles.
        $entity_reference_field_map = $this->entityFieldManager->getFieldMapByFieldType('entity_reference');
        if (empty($entity_reference_field_map[$entity_type_id]) || !isset($entity_reference_field_map[$entity_type_id][$step['field_name']])) {
          $this->context->buildViolation($constraint->stepInvalidFieldNameEntityType, [
            '%position' => $index,
            '%field' => $step['field_name'],
          ])
            ->atPath('map')
            ->addViolation();

          continue;
        }

        if (!empty($step['bundles'])) {
          foreach ($step['bundles'] as $bundle) {
            if (empty($entity_reference_field_map[$entity_type_id][$step['field_name']]['bundles'][$bundle])) {
              $this->context->buildViolation($constraint->stepInvalidFieldNameBundle, [
                '%bundle' => $bundle,
                '%position' => $index,
                '%field' => $step['field_name'],
              ])
                ->atPath('map')
                ->addViolation();
            }
          }
        }
      }
    }
  }

}
