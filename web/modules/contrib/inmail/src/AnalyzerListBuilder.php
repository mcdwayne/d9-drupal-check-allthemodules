<?php

namespace Drupal\inmail;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * List builder for message analyzer configurations.
 *
 * @ingroup analyzer
 */
class AnalyzerListBuilder extends DraggableListBuilder {

  /**
   * The message analyzer plugin manager.
   *
   * @var \Drupal\inmail\AnalyzerManagerInterface
   */
  protected $analyzerManager;

  /**
   * Constructs a new AnalyzerListBuilder.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, AnalyzerManagerInterface $analyzer_manager) {
    parent::__construct($entity_type, $storage);
    $this->analyzerManager = $analyzer_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.inmail.analyzer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $row['label'] = $this->t('Analyzer');
    $row['plugin'] = $this->t('Plugin');
    return $row + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\inmail\Entity\AnalyzerConfig $entity */
    $plugin_id = $entity->getPluginId();
    if ($this->analyzerManager->hasDefinition($plugin_id)) {
      $plugin_label = $this->analyzerManager->getDefinition($plugin_id)['label'];
    }
    else {
      $plugin_label = $this->t('Plugin missing');
    }

    $row['label'] = $this->getLabel($entity);
    $row['plugin'] = array('#markup' => $plugin_label);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $operations['edit']['title'] = $this->t('Configure');
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'inmail_analyzer_list';
  }
}
