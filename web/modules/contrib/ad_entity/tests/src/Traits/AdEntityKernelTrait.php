<?php

namespace Drupal\Tests\ad_entity\Traits;

/**
 * Trait AdEntityKernelTrait for Kernel tests.
 */
trait AdEntityKernelTrait {

  /**
   * Creates a new ad_entity instance.
   *
   * @param array $values
   *   (Optional) Further values to use for creation.
   *
   * @return \Drupal\ad_entity\Entity\AdEntityInterface
   *   The created ad_entity instance.
   */
  protected function createNewAdEntity(array $values = []) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    try {
      $storage = $this->container->get('entity_type.manager')->getStorage('ad_entity');
    }
    catch (\Exception $e) {
      return NULL;
    }
    $values += [
      'id' => 'test_entity',
      'label' => 'Test entity',
      'status' => TRUE,
      'type_plugin_id' => 'test_type',
      'view_plugin_id' => 'test_view',
    ];
    return $storage->create($values);
  }

  /**
   * Creates a new ad_display instance.
   *
   * @param array $values
   *   (Optional) Further values to use for creation.
   *
   * @return \Drupal\ad_entity\Entity\AdDisplayInterface
   *   The created ad_display instance.
   */
  protected function createNewAdDisplay(array $values = []) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    try {
      $storage = $this->container->get('entity_type.manager')->getStorage('ad_display');
    }
    catch (\Exception $e) {
      return NULL;
    }
    /** @var \Drupal\Core\Theme\ThemeManagerInterface $theme_manager */
    $theme_manager = $this->container->get('theme.manager');
    $theme_name = $theme_manager->getActiveTheme()->getName();
    $values += [
      'id' => 'test_display',
      'label' => 'Test display',
      'status' => TRUE,
      'variants' => [
        $theme_name => [
          'test_entity' => '["any"]',
        ],
      ],
    ];
    return $storage->create($values);
  }

  /**
   * Get the manager for Advertising context plugins.
   *
   * @return \Drupal\ad_entity\Plugin\AdContextManager
   *   The context manager.
   */
  protected function getContextManager() {
    try {
      return $this->container->get('ad_entity.context_manager');
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Get the view builder for Advertising entities.
   *
   * @return \Drupal\Core\Entity\EntityViewBuilderInterface
   *   The view builder for Advertising entities.
   */
  protected function getAdEntityViewBuilder() {
    try {
      /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $type_manager */
      $type_manager = $this->container->get('entity_type.manager');
      return $type_manager->getViewBuilder('ad_entity');
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Get the view builder for Display configs for Advertisement.
   *
   * @return \Drupal\Core\Entity\EntityViewBuilderInterface
   *   The view builder for Display configs for Advertisement.
   */
  protected function getAdDisplayViewBuilder() {
    try {
      /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $type_manager */
      $type_manager = $this->container->get('entity_type.manager');
      return $type_manager->getViewBuilder('ad_display');
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Get the renderer.
   *
   * @return \Drupal\Core\Render\RendererInterface
   *   The renderer.
   */
  protected function getRenderer() {
    try {
      return $this->container->get('renderer');
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Allows view access to anonymous users on ad_entity and ad_display configs.
   */
  protected function allowViewAccess() {
    $permissions = $this->config('user.role.anonymous')->get('permissions');
    $permissions = is_array($permissions) ? $permissions : [];
    $permissions[] = 'view ad_entity';
    $permissions[] = 'view ad_display';
    $this->config('user.role.anonymous')->set('permissions', $permissions)->save();
  }

}
