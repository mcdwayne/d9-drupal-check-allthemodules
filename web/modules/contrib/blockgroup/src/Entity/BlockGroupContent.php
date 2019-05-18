<?php

namespace Drupal\blockgroup\Entity;

use Drupal\block\Entity\Block;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\blockgroup\BlockGroupContentInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Block group content entity.
 *
 * @ConfigEntityType(
 *   id = "block_group_content",
 *   label = @Translation("Block group content"),
 *   handlers = {
 *     "list_builder" = "Drupal\blockgroup\BlockGroupContentListBuilder",
 *     "form" = {
 *       "add" = "Drupal\blockgroup\Form\BlockGroupContentForm",
 *       "edit" = "Drupal\blockgroup\Form\BlockGroupContentForm",
 *       "delete" = "Drupal\blockgroup\Form\BlockGroupContentDeleteForm"
 *     }
 *   },
 *   config_prefix = "block_group_content",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/block_group_content/{block_group_content}",
 *     "edit-form" = "/admin/structure/block_group_content/{block_group_content}/edit",
 *     "delete-form" = "/admin/structure/block_group_content/{block_group_content}/delete",
 *     "collection" = "/admin/structure/visibility_group"
 *   }
 * )
 */
class BlockGroupContent extends ConfigEntityBase implements BlockGroupContentInterface {

  /**
   * The Block group content ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Block group content label.
   *
   * @var string
   */
  protected $label;

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    $this->clearBlocksCaches();
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    self::clearBlocksCaches();
  }

  /**
   * Clear caches on delete and create for blocks to show regions and blocks.
   */
  public static function clearBlocksCaches() {
    // @todo Rework this once we have plugin deriver cacheability support in
    // https://www.drupal.org/project/drupal/issues/2633878
    // @see \Drupal\system\Entity\Menu::save
    // Invalidate the block cache to update derivatives.
    // @see \Drupal\blockgroup\Plugin\Derivative\BlockGroups::getDerivativeDefinitions
    if (\Drupal::moduleHandler()->moduleExists('block')) {
      \Drupal::service('plugin.manager.block')->clearCachedDefinitions();
    }

    // Refresh theme region list.
    // @see blockgroup_system_info_alter()
    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
    $theme_handler = \Drupal::service('theme_handler');
    $theme_handler->refreshInfo();

    // Render arrays will be cleared when ConfigEntityBase invalidates the cache
    // tag of this config entity, added in
    // @see \Drupal\blockgroup\Plugin\Block\BlockGroup::build
  }

}
