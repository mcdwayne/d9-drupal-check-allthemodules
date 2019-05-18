<?php

namespace Drupal\ad_entity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Theme\ActiveTheme;

/**
 * Defines Display configurations for Advertisement.
 *
 * @ConfigEntityType(
 *   id = "ad_display",
 *   label = @Translation("Display for Advertisement"),
 *   label_collection = @Translation("Display configs for Advertisement"),
 *   label_singular = @Translation("Display for Advertisement"),
 *   label_plural = @Translation("Display configs for Advertisement"),
 *   handlers = {
 *     "list_builder" = "Drupal\ad_entity\AdDisplayListBuilder",
 *     "view_builder" = "Drupal\ad_entity\AdDisplayViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\ad_entity\Form\AdDisplayForm",
 *       "edit" = "Drupal\ad_entity\Form\AdDisplayForm",
 *       "delete" = "Drupal\ad_entity\Form\AdDisplayDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\ad_entity\AdDisplayHtmlRouteProvider",
 *     },
 *    "access" = "Drupal\entity\EntityAccessControlHandler",
 *    "permission_provider" = "Drupal\entity\EntityPermissionProvider"
 *   },
 *   config_prefix = "display",
 *   admin_permission = "administer ad_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/ad-display/{ad_display}",
 *     "add-form" = "/admin/structure/ad_entity/display/add",
 *     "edit-form" = "/admin/structure/ad_entity/display/{ad_display}/edit",
 *     "delete-form" = "/admin/structure/ad_entity/display/{ad_display}/delete",
 *     "collection" = "/admin/structure/ad_entity/display"
 *   }
 * )
 */
class AdDisplay extends ConfigEntityBase implements AdDisplayInterface {

  /**
   * The display ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The display label.
   *
   * @var string
   */
  protected $label;

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    if (!empty($this->get('variants'))) {
      foreach ($this->get('variants') as $theme_variants) {
        if (!empty($theme_variants)) {
          foreach (array_keys($theme_variants) as $id) {
            $dependency = 'ad_entity.ad_entity.' . $id;
            $this->addDependency('config', $dependency);
          }
        }
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $this->addCacheContexts(['url.path']);
    return parent::getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getVariantsForTheme(ActiveTheme $theme) {
    $theme_name = $theme->getName();
    $variants = $this->get('variants') ?: [];
    if (empty($variants[$theme_name])) {
      // Check for enabled fallback settings, and switch to these when given.
      $fallback = $this->get('fallback') ?: [];
      if (!empty($fallback['use_settings_from'])) {
        $theme_name = $fallback['use_settings_from'];
      }
      if (!empty($fallback['use_base_theme'])) {
        foreach ($theme->getBaseThemes() as $base_theme) {
          if (!empty($variants[$base_theme->getName()])) {
            $theme_name = $base_theme->getName();
            break;
          }
        }
      }
    }
    return !empty($variants[$theme_name]) ? $variants[$theme_name] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    $this->invalidateBlockCache();
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();
    $this->invalidateBlockCache();
  }

  /**
   * Invalidates the block cache to update ad_display derivatives.
   */
  protected function invalidateBlockCache() {
    if (\Drupal::moduleHandler()->moduleExists('block')) {
      \Drupal::service('plugin.manager.block')->clearCachedDefinitions();
    }
  }

}
