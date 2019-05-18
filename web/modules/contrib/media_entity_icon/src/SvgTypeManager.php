<?php

namespace Drupal\media_entity_icon;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Simplifies gathering informations of SVG media types.
 *
 * @package Drupal\media_entity_icon
 */
class SvgTypeManager implements SvgTypeManagerInterface {
  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Media type manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $mediaTypeManager;

  /**
   * Static cache of icon bundle ids.
   *
   * @var array
   */
  protected $iconBundleIds;

  /**
   * Static cache of icon bundles config fields.
   *
   * @var array
   */
  protected $iconBundlesConfig;

  /**
   * Static cache of sprite bundle ids.
   *
   * @var array
   */
  protected $spriteBundleIds;

  /**
   * Static cache of sprite bundles config fields.
   *
   * @var array
   */
  protected $spriteBundlesConfig;

  /**
   * SvgTypeManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $media_type_manager
   *   Media type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PluginManagerInterface $media_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->mediaTypeManager = $media_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getIconBundleNames($reset = FALSE) {
    if ($reset || !isset($this->iconBundleIds)) {
      $this->iconBundleIds = [];
      $media_bundles = $this->mediaTypeManager->getDefinitions();
      foreach ($media_bundles as $id => $definition) {
        if ('Drupal\media_entity_icon\Plugin\MediaEntity\Type\SvgIcon' === $definition['class']) {
          $this->iconBundleIds[$id] = $definition['label'];
        }
      }
    }

    return $this->iconBundleIds;
  }

  /**
   * {@inheritdoc}
   */
  public function getIconBundleConfigs($reset = FALSE) {
    if ($reset || !isset($this->iconBundlesConfig)) {
      $this->iconBundlesConfig = [];

      // Load media bundles ...
      $icon_bundle_ids = array_keys($this->getIconBundleNames());
      $icon_bundles = [];
      if (!empty($icon_bundle_ids)) {
        $icon_bundles = $this->entityTypeManager
          ->getStorage('media_bundle')
          ->loadMultiple($icon_bundle_ids);
      }

      // ... to get the config.
      foreach ($icon_bundles as $icon_bundle_id => $icon_bundle) {
        $this->iconBundlesConfig[$icon_bundle_id] = $icon_bundle->getTypeConfiguration();
      }
    }

    return $this->iconBundlesConfig;
  }

  /**
   * {@inheritdoc}
   */
  public function getIconBundleConfig($bundle_id, $reset = FALSE) {
    if ($reset || !isset($this->iconBundlesConfig[$bundle_id])) {
      $this->iconBundlesConfig[$bundle_id] = [];

      // Load media bundle ...
      $icon_bundle = $this->entityTypeManager
        ->getStorage('media_bundle')
        ->load($bundle_id);

      // ... to get the config.
      $this->iconBundlesConfig[$bundle_id] = $icon_bundle->getTypeConfiguration();
    }

    return $this->iconBundlesConfig[$bundle_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getIconBundleSourceField($bundle_id, $reset = FALSE) {
    $icon_bundle_config = $this->getIconBundleConfig($bundle_id, $reset);

    return isset($icon_bundle_config['source_field']) ? $icon_bundle_config['source_field'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getIconBundleIdField($bundle_id, $reset = FALSE) {
    $icon_bundle_config = $this->getIconBundleConfig($bundle_id, $reset);

    return isset($icon_bundle_config['id_field']) ? $icon_bundle_config['id_field'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSpriteBundleNames($reset = FALSE) {
    if ($reset || !isset($this->spriteBundleIds)) {
      $this->spriteBundleIds = [];
      $media_bundles = $this->mediaTypeManager->getDefinitions();
      foreach ($media_bundles as $id => $definition) {
        if ('Drupal\media_entity_icon\Plugin\MediaEntity\Type\SvgSprite' === $definition['class']) {
          $this->spriteBundleIds[$id] = $definition['label'];
        }
      }
    }

    return $this->spriteBundleIds;
  }

}
