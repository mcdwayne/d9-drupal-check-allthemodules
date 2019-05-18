<?php
namespace Drupal\elasticsearch_connect\Elasticsearch;

use Drupal\Core\Entity\EntityInterface;

interface IndexManagerInterface {
  
  /**
   * Insert/Update entity info into index.
   * 
   * @param EntityInterface $entity
   *   Entity being indexed
   * @param string $op
   *   ongoing operation. Could be 'insert' or 'update'
   */
  public function updateEntityIndex(EntityInterface $entity, $op);
  
  /**
   * Delete entity from index
   * 
   * @param EntityInterface $entity
   *   Entity being deleted
   */
  public function deleteEntityIndex(EntityInterface $entity);
  
}