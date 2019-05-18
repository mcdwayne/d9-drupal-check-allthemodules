<?php

namespace Drupal\prod_check;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\prod_check\Plugin\ProdCheckPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of prod check processor entities.
 *
 * @see \Drupal\prod_check\Entity\ProdCheckProcessor
 */
class CheckListBuilder extends ConfigEntityListBuilder {

  /**
   * @var bool
   */
  protected $hasConfigurableChecks = FALSE;

  /**
   * The processor plugin manager.
   *
   * @var \Drupal\prod_check\Plugin\ProdCheckPluginManager
   */
  protected $processorManager;

  /**
   * Constructs a new ProcessorListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The processor storage.
   * @param \Drupal\prod_check\Plugin\ProdCheckPluginManager $manager
   *   The processor plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, ProdCheckPluginManager $manager) {
    parent::__construct($entity_type, $storage);

    $this->processorManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.prod_check')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = parent::load();
    foreach ($entities as $entity) {
      if ($entity->isConfigurable()) {
        $this->hasConfigurableChecks = TRUE;
        continue;
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    if ($this->hasConfigurableChecks) {
      $row += parent::buildRow($entity);
    }
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = array(
        'label' => t('Label'),
      ) + parent::buildHeader();
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = $entity->isConfigurable() ? parent::getDefaultOperations($entity) : array();

    // Unset delete operation to prevent mistakes
    unset($operations['delete']);

    // Rename edit operation
    if (isset($operations['edit'])) {
      $operations['edit']['title'] = t('Configure');
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['check_header']['#markup'] = '<h3>' . t('Available production checks:') . '</h3>';
    $build['check_table'] = parent::render();
    return $build;
  }

}
