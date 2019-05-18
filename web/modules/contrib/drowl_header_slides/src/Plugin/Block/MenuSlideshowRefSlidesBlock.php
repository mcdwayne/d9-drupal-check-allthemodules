<?php

namespace Drupal\drowl_header_slides\Plugin\Block;

use \Drupal\Core\Block\BlockBase;
use \Drupal\Core\Cache\Cache;

/**
 * Provides a 'DROWL Header Slides Menu Slideshow Slide Block' Block.
 *
 * @Block(
 *   id = "drowl_header_menu_slideshow_ref_block",
 *   admin_label = @Translation("DROWL Header Slides Menu Slideshow Slide Block"),
 *   category = @Translation("DROWL Header Slides"),
 * )
 */
class MenuSlideshowRefSlidesBlock extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    $activeTrailMenuLinkEntity = $this->determineActiveTrailMenuLinkEntity();
    if (!empty($activeTrailMenuLinkEntity)) {
      /**
       * @var $mediaSlideshowEntity \Drupal\media_entity\Entity\Media
       */
      $mediaSlideshowEntity = $this->determineMenuLinkMediaSlideshowEntity($activeTrailMenuLinkEntity);
      if (!empty($mediaSlideshowEntity) && $mediaSlideshowEntity->access('view')) {
        // Return the rendered media entity:
        $build = \Drupal::entityTypeManager()->getViewBuilder('media')->view($mediaSlideshowEntity);
        return $build;
      }
    }
    return null;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts()
  {
    //Every new route this block will rebuild
    return Cache::mergeContexts(parent::getCacheContexts(), array('route'));
  }

  /**
   * Returns the Media slideshow entity from the given menu link
   *
   * @param \Drupal\menu_link_content\MenuLinkContentInterface $menuActiveLinkEntity
   * @return \Drupal\media_entity\Entity\Media
   */
  protected function determineMenuLinkMediaSlideshowEntity(\Drupal\menu_link_content\Entity\MenuLinkContent $menuLinkEntity = null)
  {
    if (empty($menuLinkEntity)) {
      return null;
    }
    if ($menuLinkEntity->hasField('field_slideshow_ref')) {
      /**
       * @var $mediaSlideshowEntity \Drupal\media_entity\Entity\Media
       */
      $mediaSlideshowEntity = $menuLinkEntity->field_slideshow_ref->entity;
      if (!empty($mediaSlideshowEntity)) {
        return $mediaSlideshowEntity;
      }
    }
      // Has no own slideshow selected - check for parent inheritance:
    return $this->determineMenuLinkMediaSlideshowEntity($this->determineParentMenuLink($menuLinkEntity));
  }

  /**
   * Returns the parent menu item from the given $menuLinkEntity
   *
   * @param \Drupal\menu_link_content\MenuLinkContentInterface $menuLinkEntity
   * @return \Drupal\menu_link_content\MenuLinkContentInterface
   */
  protected function determineParentMenuLink(\Drupal\menu_link_content\Entity\MenuLinkContent $menuLinkEntity = null)
  {
    if (empty($menuLinkEntity)) {
      return null;
    }
    // Load the parent menu id:
    /**
     * @var $parentMenuLinkEntityId string
     */
    $parentMenuLinkEntityId = $menuLinkEntity->getParentId();
    if (!empty($parentMenuLinkEntityId)) {      
      // Has parent:
      /**
       * @var $menu_link_manager \Drupal\Core\Menu\MenuLinkManager
       */
      $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
      /**
       * @var Drupal\menu_link_content\Plugin\Menu\MenuLinkContent
       */
      $parentMenuLinkManagerInstance = $menu_link_manager->createInstance($parentMenuLinkEntityId);
      /**
       * @var $parentMenuLinkManagerInstanceDerivateId string
       */
      $parentMenuLinkManagerInstanceDerivateId = $parentMenuLinkManagerInstance->getDerivativeId();
      // Load the parent menu_item entity
      /**
       * @var $parentMenuLinkEntity \Drupal\menu_item_extras\Entity\MenuItemExtrasMenuLinkContent
       */
      $parentMenuLinkEntity = \Drupal::service('entity.repository')
        ->loadEntityByUuid('menu_link_content', $parentMenuLinkManagerInstanceDerivateId);
      if (!empty($parentMenuLinkEntity) && $parentMenuLinkEntity->hasField('field_slideshow_inherit')) {
        // The parent menu item has a field_slideshow_inherit field.
        /**
         * @var $inheritFromParent boolean
         */
        $inheritFromParent = !empty($parentMenuLinkEntity->get('field_slideshow_inherit')->value);
        if ($inheritFromParent) {
          // Inherit from parent is enabled. Return the parent as slideshow provider:
          return $parentMenuLinkEntity;
        }
      }
    }
    return null;
  }

  /**
   * Returns the currently active MenuLinkContent entity.
   *
   * @return \Drupal\menu_link_content\MenuLinkContentInterface
   */
  protected function determineActiveTrailMenuLinkEntity()
  {
    // Cache per site call:
    $menuActiveLinkEntity = &drupal_static(__FUNCTION__, null);
    if (isset($menuActiveLinkEntity)) {
      return $menuActiveLinkEntity ? : null;
    }

    $menuActiveTrail = \Drupal::service('menu.active_trail');
    $searchInMenus = \Drupal::config('drowl_header_slides.settings')->get('menus');
    $menuActiveLink = null;
    if (!empty($searchInMenus)) {
      foreach ($searchInMenus as $menu) {
        // Hint: Drupal treats the first menu with an active link
        // as the matching menu, whatever we do... Our
        // current workaround is to only use the selected menu(s).
        // It Would be better to sort the menus for evaluation and check
        // them in the configured order, IF they contain a drowl_header_slide reference.
        $menuActiveLink = $menuActiveTrail->getActiveLink($menu);
        break;
      }
    }
    if (empty($menuActiveLink)) {
      return null;
    }
    $menuActiveLinkUuid = $menuActiveLink->getDerivativeId();
    if (empty($menuActiveLinkUuid)) {
      return null;
    }
    $menuActiveLinkEntity = \Drupal::service('entity.repository')
      ->loadEntityByUuid('menu_link_content', $menuActiveLinkUuid);
    if (!empty($menuActiveLinkEntity)) {
      return $menuActiveLinkEntity;
    }
    return null;
  }

}
