<?php

namespace Drupal\sitemeta;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of Site meta entities.
 *
 * @ingroup sitemeta
 */
class SiteMetaListBuilder extends EntityListBuilder {

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, AliasManagerInterface $alias_manager) {
    parent::__construct($entity_type, $storage);
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('path.alias_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['path'] = $this->t('Path');
    $header['alias'] = $this->t('Alias');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\sitemeta\Entity\SiteMeta */
    $row['name'] = $entity->label();
    $row['path'] = $this->aliasManager->getPathByAlias($entity->getPath());
    $row['alias'] = $this->aliasManager->getAliasByPath($entity->getPath());
    return $row + parent::buildRow($entity);
  }

}
