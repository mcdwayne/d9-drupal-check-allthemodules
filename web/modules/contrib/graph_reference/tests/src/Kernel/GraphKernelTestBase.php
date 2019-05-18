<?php

namespace Drupal\Tests\graph_reference\Kernel;

use Drupal\graph_reference\Entity\Graph;
use Drupal\graph_reference\Entity\GraphInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 *
 * @group graph_reference
 */
abstract class GraphKernelTestBase extends EntityKernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'graph_reference',
    'dynamic_entity_reference'
  ];

  /**
   * Creates (and saves) a new graph entity.
   *
   * @param array $values
   * @return \Drupal\graph_reference\Entity\GraphInterface
   */
  protected function createGraph($values = []) {
    $values += [
      'id' => $this->randomMachineName(),
      'label' => $this->randomString()
    ];
    if (!isset($values['vertex_set'])) {
      $values['vertex_set'] = [
        'plugin_id' => 'entity_type',
        'options' => [
          'entity_type' => 'user'
        ]
      ];
    }

    $graph = Graph::create($values);
    $graph->enforceIsNew()->save();

    return $graph;
  }

  /**
   * The field definition of the computed graph reference field
   *
   * @param \Drupal\graph_reference\Entity\GraphInterface $graph
   * @param string $entity_type
   * @param null $bundle
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected function getGraphReferenceFieldDefinition(GraphInterface $graph, $entity_type = 'user', $bundle = NULL) {
    $reference_field_name = $graph->getReferenceFieldName();
    return $this->entityManager->getFieldDefinitions($entity_type, $bundle ? : $entity_type)[$reference_field_name];
  }

}