<?php

namespace Drupal\entity_access_audit\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\entity_access_audit\AccessAuditResultCollection;
use Drupal\entity_access_audit\Dimensions\BundleDimension;
use Drupal\entity_access_audit\Dimensions\EntityOwnerDimension;
use Drupal\entity_access_audit\Dimensions\OperationDimension;
use Drupal\entity_access_audit\Dimensions\RoleDimension;

/**
 * Controller for entity access audit.
 *
 * This controller supports displaying information from an
 * AccessAuditResultCollection with any supported cross section of dimensions.
 * Varying dimensions across entity types, as enforced by the
 * AccessAuditResultCollectionFactory are: bundleable or not, having entity
 * owner or not.
 */
class AuditDetailsController extends AuditControllerBase {

  /**
   * Details on an individual entity type.
   */
  public function details($entity_type_id) {
    $audit_result = $this->auditManager->getAuditForEntityType($entity_type_id);

    $dimensions = [
      '#title' => t('Audit Dimensions'),
      '#theme' => 'item_list',
      '#attached' => [
        'library' => [
          'system/base',
        ],
      ],
    ];
    foreach ($audit_result->getDimensionClasses() as $dimension_class) {
      $dimensions['#items'][] = $dimension_class::getLabel();
    }

    return [
      'metadata' => [
        '#type' => 'details',
        '#title' => t('Metadata'),
        'children' => [
          $dimensions,
          ['#markup' => t('Total access checks: @count', ['@count' => $audit_result->count()])],
        ],
      ],
      $this->getMainAuditTable($audit_result),
    ];
  }

  /**
   * Get the main audit table for access results.
   *
   * @param \Drupal\entity_access_audit\AccessAuditResultCollection $result_collection
   *   A collection of access audit results.
   *
   * @return array
   *   A table of analysis.
   */
  protected function getMainAuditTable($result_collection) {
    $table = [
      '#type' => 'table',
      '#sticky' => TRUE,
    ];
    $table['#header'][] = '';

    // The table header will consist of operations.
    foreach ($result_collection->getDimensionsOfType(OperationDimension::class) as $operation_dimension) {
      $table['#header'][] = $operation_dimension->getDimensionValue();
    }

    // Roles are always a valid dimension, start with that for the top level of
    // the table.
    foreach ($result_collection->getDimensionsOfType(RoleDimension::class) as $role_dimension) {
      $row = &$table['#rows'][];
      $row[] = new FormattableMarkup('<strong>@role_name</strong>', ['@role_name' => $role_dimension->getDimensionValue()]);

      // When bundles are displayed in the table, create an empty row for the
      // rest of the role. Each bundle will have its own row under the role
      // with an operation result in each cell.
      if ($result_collection->hasDimensionType(BundleDimension::class)) {
        $row[] = [
          'colspan' => 4,
          'data' => '',
        ];
        foreach ($result_collection->getDimensionsOfType(BundleDimension::class) as $bundle_dimension) {

          // Display the bundle name in the first cell.
          $row = &$table['#rows'][];
          $row[] = $bundle_dimension->getDimensionValue();

          // Inside the bundle row, create a cell for each operation.
          foreach ($result_collection->getDimensionsOfType(OperationDimension::class) as $operation_dimension) {

            // We will need to format the inner cell different if the entity
            // type has an entity owner dimension. Display info about entities
            // that are both owned and not owned by the given test user.
            if ($result_collection->hasDimensionType(EntityOwnerDimension::class)) {
              $row[]['data'] = $this->formatEntityOwnerDimension($result_collection, [
                $role_dimension,
                $operation_dimension,
                $bundle_dimension,
              ]);
            }
            else {
              $audit_result = $result_collection->getAuditResultMatchingDimensions([
                $role_dimension,
                $operation_dimension,
                $bundle_dimension,
              ]);
              $row[] = ['data' => $this->formatAccessAuditResult($audit_result)];
            }

          }
        }
      }
      // For non bundleable entity types, simply format each operation for a
      // given role adjacent to cell containing that role.
      else {
        foreach ($result_collection->getDimensionsOfType(OperationDimension::class) as $operation_dimension) {
          // As with bundleable entity types, support displaying results per
          // entity owner.
          if ($result_collection->hasDimensionType(EntityOwnerDimension::class)) {
            $row[]['data'] = $this->formatEntityOwnerDimension($result_collection, [$role_dimension, $operation_dimension]);
          }
          else {
            $audit_result = $result_collection->getAuditResultMatchingDimensions([
              $role_dimension,
              $operation_dimension,
            ]);
            $row[] = ['data' => $this->formatAccessAuditResult($audit_result)];
          }
        }
      }
    }

    return $table;
  }

  /**
   * Format the entity owner dimension in a single cell.
   *
   * @param \Drupal\entity_access_audit\AccessAuditResultCollection $result_collection
   *   The result collection to pull the entity owner dimension from.
   * @param array $other_dimensions
   *   Other dimensions to pull access audit results for.
   *
   * @return array
   *   A render array for a single cell.
   */
  protected function formatEntityOwnerDimension(AccessAuditResultCollection $result_collection, $other_dimensions) {
    $cell = [];
    foreach ($result_collection->getDimensionsOfType(EntityOwnerDimension::class) as $entity_owner_dimension) {
      $audit_result = $result_collection->getAuditResultMatchingDimensions(array_merge($other_dimensions, [$entity_owner_dimension]));
      $cell[] = [
        $this->formatAccessAuditResult($audit_result),
        ['#markup' => $entity_owner_dimension->getDimensionValue()],
        ['#markup' => '<br/>'],
      ];
    }
    return $cell;
  }

  /**
   * Label callback for ::details.
   */
  public static function detailsTitle($entity_type_id) {
    return t('@entity_label Access Audit', ['@entity_label' => \Drupal::entityTypeManager()->getDefinition($entity_type_id)->getLabel()]);
  }

}
