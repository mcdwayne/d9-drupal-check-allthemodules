<?php

namespace Drupal\evergreen;

use Drupal\views\EntityViewsData;
use Drupal\evergreen\EvergreenServiceInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the views data for the node entity type.
 */
class EvergreenContentViewsData extends EntityViewsData {

  protected $evergreenPlugins;
  protected $evergreen;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, SqlEntityStorageInterface $storage_controller, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, TranslationInterface $translation_manager, PluginManagerInterface $evergreen_plugins) {
    $this->entityType = $entity_type;
    $this->entityManager = $entity_manager;
    $this->storage = $storage_controller;
    $this->moduleHandler = $module_handler;
    $this->setStringTranslation($translation_manager);
    $this->evergreenPlugins = $evergreen_plugins;
    // $this->evergreen = $evergreen;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('string_translation'),
      $container->get('typed_data_manager'),
      $container->get('plugin.manager.evergreen')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // $defs = $this->evergreenPlugins->getDefinitions();
    // foreach ($defs as $def) {
    //   $plugin = $this->evergreenPlugins->createInstance($def);
    //   $plugin->getViewsData($data);
    // }

    return $data;
    // $data['evergreen_content'] = [
    //
    // ];
  }

}
