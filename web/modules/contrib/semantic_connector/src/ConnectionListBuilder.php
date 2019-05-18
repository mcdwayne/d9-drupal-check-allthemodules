<?php

/**
 * @file
 * Contains \Drupal\semantic_connector\ConnectionListBuilder.
 */

namespace Drupal\semantic_connector;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\semantic_connector\Entity\SemanticConnectorConnectionInterface;

class ConnectionListBuilder extends ConfigEntityListBuilder
{
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = t('Title');
    $header['type'] = t('Type');
    return $header + parent::buildHeader();
  }
  /**
   * {@inheritdoc}
   */
  public function buildRow(SemanticConnectorConnectionInterface $entity) {
    $row['title'] = $entity->get('title');
    $row['type'] = $entity->get('type');
    return $row + parent::buildRow($entity);
  }

  /**
   * Gets this list's default operations.
   *
   * This method calls the parent method, then adds in an operation
   * to create an entity of this type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the operations are for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  /*public function getDefaultOperations(SemanticConnectorConnectionInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    $url = \Drupal\Core\Url::fromRoute('entity.pp_server_connection.edit_form', ['pp_server_connection' => $entity->id()]);
    $operations['edit'] = array(
      'title' => $this->t('Edit'),
      'weight' => 10,
      'url' =>  $url,
    );

    return $operations;
  }*/
}