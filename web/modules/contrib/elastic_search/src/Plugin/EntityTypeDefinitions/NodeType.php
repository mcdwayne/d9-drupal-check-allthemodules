<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 12/10/16
 * Time: 13:21
 */

namespace Drupal\elastic_search\Plugin\EntityTypeDefinitions;

use Drupal\elastic_search\Annotation\EntityTypeDefinitions;
use Drupal\elastic_search\Plugin\EntityTypeDefinitionsBase;

/**
 * Class User
 * Returns an array of fields for User entity types.
 * This removes most of the fields that you dont care about, leaving only the ID
 *
 * @EntityTypeDefinitions(
 *   id = "node_type",
 *   label = @Translation("node type")
 * )
 */
class NodeType extends EntityTypeDefinitionsBase {

  /**
   * @param string $entityType
   * @param string $bundleType
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   */
  public function getFieldDefinitions(string $entityType, string $bundleType) {
    $fields = $this->entityFieldManager->getFieldDefinitions($entityType,
                                                             $bundleType);
    return [];
  }

}