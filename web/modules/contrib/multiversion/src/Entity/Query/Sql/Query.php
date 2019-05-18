<?php

namespace Drupal\multiversion\Entity\Query\Sql;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\Sql\Query as CoreQuery;
use Drupal\multiversion\Entity\Query\QueryInterface;
use Drupal\multiversion\Entity\Query\QueryTrait;

class Query extends CoreQuery implements QueryInterface {

  use QueryTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\multiversion\MultiversionManager
   */
  protected $multiversionManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, $conjunction, Connection $connection, array $namespaces) {
    parent::__construct($entity_type, $conjunction, $connection, $namespaces);
    $this->entityTypeManager = \Drupal::service('entity_type.manager');
    $this->multiversionManager = \Drupal::service('multiversion.manager');
  }

}
