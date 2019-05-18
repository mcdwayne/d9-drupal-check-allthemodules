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
 * Class Generic
 * Returns an array of fields for generic entity types. Almost everything can
 * use this plugin
 *
 * @EntityTypeDefinitions(
 *   id = "generic",
 *   label = @Translation("generic")
 * )
 */
class Generic extends EntityTypeDefinitionsBase {

  /**
   * @param string $entityType
   * @param string $bundleType
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   */
  public function getFieldDefinitions(string $entityType, string $bundleType) {
    return $this->entityFieldManager->getFieldDefinitions($entityType,
                                                          $bundleType);
  }

}