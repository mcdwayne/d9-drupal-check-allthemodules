<?php

namespace Drupal\entity_access_audit\Controller;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Drupal\entity_access_audit\Dimensions\OperationDimension;
use Drupal\entity_access_audit\Dimensions\RoleDimension;

/**
 * Controller for entity access audit.
 */
class AuditOverviewController extends AuditControllerBase {

  /**
   * Overview page for showing entity types.
   */
  public function overview() {
    $config_entity_types = array_filter($this->auditManager->getApplicableEntityTypes(), function(EntityTypeInterface $entity_type) {
      return $entity_type instanceof ConfigEntityTypeInterface;
    });
    $content_entity_types = array_filter($this->auditManager->getApplicableEntityTypes(), function(EntityTypeInterface $entity_type) {
      return $entity_type instanceof ContentEntityTypeInterface;
    });

    return [
      [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => t('Content Entity Types'),
      ],
      $this->getTableForEntityTypes($content_entity_types),
      [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => t('Configuration Entity Types'),
      ],
      $this->getTableForEntityTypes($config_entity_types),
    ];
  }

  /**
   * Get an overview table for the given entity types.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   Entity types.
   *
   * @return array
   *   A table for the given entity types.
   */
  protected function getTableForEntityTypes($entity_types) {
    $overview = [
      '#type' => 'table',
      '#header' => [
        t('Name'),
        t('C R U D (Anonymous User)'),
        '',
      ],
    ];
    foreach ($entity_types as $definition) {
      $row = &$overview['#rows'][];
      $row[] = $definition->getLabel();

      $anonymous_user_crud_results = [];
      $overview_audit = $this->auditManager->getOverviewAuditForEntityType($definition->id());
      $role_dimension = $overview_audit->getDimensionsOfType(RoleDimension::class)[0];
      foreach ($overview_audit->getDimensionsOfType(OperationDimension::class) as $operation) {
        $result = $overview_audit->getAuditResultMatchingDimensions([$operation, $role_dimension]);
        $anonymous_user_crud_results[] = $this->formatAccessAuditResult($result);
      }
      $row[] = [
        'data' => $anonymous_user_crud_results,
      ];

      $row[] = ['data' => [
        '#type' => 'link',
        '#title' => 'More Info',
        '#url' => Url::fromRoute('entity_access_audit.details', [
          'entity_type_id' => $definition->id(),
        ]),
        '#options' => [
          'attributes' => [
            'class' => 'button',
          ],
        ],
      ]];
    }

    return $overview;
  }

}
