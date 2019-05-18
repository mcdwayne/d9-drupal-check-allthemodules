<?php

namespace Drupal\ad_entity;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides the view builder for Display configs for Advertisement.
 */
class AdDisplayViewBuilder extends EntityViewBuilder {

  /**
   * The theme manager service.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The storage for Advertising entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $adEntityStorage;

  /**
   * The view builder for Advertising entities.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $adEntityViewBuilder;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $type_manager = $container->get('entity_type.manager');
    /** @var \Drupal\ad_entity\AdDisplayViewBuilder $instance */
    $instance = parent::createInstance($container, $entity_type);
    $instance->setThemeManager($container->get('theme.manager'));
    $instance->setAdEntityStorage($type_manager->getStorage('ad_entity'));
    $instance->setAdEntityViewBuilder($type_manager->getViewBuilder('ad_entity'));
    return $instance;
  }

  /**
   * Get the theme manager.
   *
   * @return \Drupal\Core\Theme\ThemeManagerInterface
   *   The theme manager.
   */
  public function getThemeManager() {
    return $this->themeManager;
  }

  /**
   * Set the theme manager.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager to set.
   *
   * @return \Drupal\ad_entity\AdDisplayViewBuilder
   *   The view builder itself.
   */
  public function setThemeManager(ThemeManagerInterface $theme_manager) {
    $this->themeManager = $theme_manager;
    return $this;
  }

  /**
   * Get the storage for Advertising entities.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The storage object.
   */
  public function getAdEntityStorage() {
    return $this->adEntityStorage;
  }

  /**
   * Set the storage for Advertising entities.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The storage for Advertising entities.
   *
   * @return \Drupal\ad_entity\AdDisplayViewBuilder
   *   The view builder itself.
   */
  public function setAdEntityStorage(EntityStorageInterface $storage) {
    $this->adEntityStorage = $storage;
    return $this;
  }

  /**
   * Get the view builder for Advertising entities.
   *
   * @return \Drupal\Core\Entity\EntityViewBuilderInterface
   *   The view builder for Advertising entities.
   */
  public function getAdEntityViewBuilder() {
    return $this->adEntityViewBuilder;
  }

  /**
   * Set the view builder for Advertising entities.
   *
   * @param \Drupal\Core\Entity\EntityViewBuilderInterface $view_builder
   *   The view builder for Advertising entities.
   *
   * @return \Drupal\ad_entity\AdDisplayViewBuilder
   *   The view builder itself.
   */
  public function setAdEntityViewBuilder(EntityViewBuilderInterface $view_builder) {
    $this->adEntityViewBuilder = $view_builder;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {}

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'default', $langcode = NULL) {
    /** @var \Drupal\ad_entity\Entity\AdDisplayInterface $entity */
    // @see AdEntityViewBuilder::view() why no cache keys are being used.
    $build = [
      '#cache' => [
        'tags' => Cache::mergeTags($this->getCacheTags(), $entity->getCacheTags()),
        'contexts' => $entity->getCacheContexts(),
        'max-age' => $entity->getCacheMaxAge(),
      ],
      '#theme' => 'ad_display',
      '#ad_display' => $entity,
      '#variants' => [],
    ];
    // When given, load and view the assigned Advertisement.
    $theme = $this->themeManager->getActiveTheme();
    foreach ($entity->getVariantsForTheme($theme) as $id => $variant) {
      if ($ad_entity = $this->adEntityStorage->load($id)) {
        $view = $this->adEntityViewBuilder->view($ad_entity, $variant);
        if ($ad_entity->access('view')) {
          $build['#variants'][$id] = $view;
        }
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'default', $langcode = NULL) {
    $build = [];
    foreach ($entities as $entity) {
      $build[$entity->id()] = $this->view($entity, $view_mode, $langcode);
    }
    return $build;
  }

}
