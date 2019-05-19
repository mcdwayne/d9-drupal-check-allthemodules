<?php

/**
 * @file
 * Contains \Drupal\semantic_connector\ConnectionListBuilder.
 */

namespace Drupal\smart_glossary;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\smart_glossary\Entity\SmartGlossaryConfigInterface;

class SmartGlossaryConfigListBuilder extends ConfigEntityListBuilder
{
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = t('Title');
    $header['basePath'] = t('Path');
    return $header + parent::buildHeader();
  }
  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var SmartGlossaryConfigInterface $entity */
    $row['title'] = $entity->get('title');
    $row['basePath'] = $entity->getBasePath();
    return $row + parent::buildRow($entity);
  }

  public function buildOperations(EntityInterface $entity) {
    $build = array(
      '#type' => 'operations',
      '#links' => $this->getOperations($entity),
    );

    $build['#links']['clone'] = array(
      'title' => t('Clone'),
      'url' => Url::fromRoute('entity.smart_glossary.clone_form', array('smart_glossary' => $entity->id())),
      'weight' => 1000,
    );

    return $build;
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