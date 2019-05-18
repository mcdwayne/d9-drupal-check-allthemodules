<?php
/**
 * @file
 * Contains \Drupal\collect\Model\ModelListBuilder.
 */

namespace Drupal\collect\Model;

use Drupal\collect\Model\ModelManagerInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds a table listing over model entities.
 */
class ModelListBuilder extends ConfigEntityListBuilder {

  /**
   * The injected model plugin manager.
   *
   * @var \Drupal\collect\Model\ModelManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a ModelListBuilder.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, ModelManagerInterface $plugin_manager) {
    parent::__construct($entity_type, $storage);
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.collect.model')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['uri_pattern'] = $this->t('URI pattern');
    $header['plugin'] = $this->t('Model Plugin');

    $header += parent::buildHeader();
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\collect\Entity\Model $entity */
    try {
      $plugin = $this->pluginManager->createInstance($entity->getPluginId());
      $plugin_label = $plugin->getLabel();
    }
    catch (PluginNotFoundException $e) {
      $plugin_label = $this->t('(Plugin missing)');
    }

    $row['label'] = $entity->label();
    $row['uri_pattern'] = $entity->getUriPattern();
    $row['plugin'] = $plugin_label;

    $row += parent::buildRow($entity);
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if ($entity->access('update')) {
      // Add operation for configuring processors.
      $operations['processing'] = [
        'title' => t('Manage processing'),
        'weight' => 20,
        'url' => $entity->urlInfo('processing-form'),
      ];
    }
    return $operations;
  }

}
