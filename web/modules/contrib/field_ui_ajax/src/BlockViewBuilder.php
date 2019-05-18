<?php

/**
 * @file
 * Contains \Drupal\field_ui_ajax\BlockViewBuilder.
 */

namespace Drupal\field_ui_ajax;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Render\Element;

/**
 * Provides a Block view builder.
 */
class BlockViewBuilder extends EntityViewBuilder {

  /**
   * #pre_render callback for building a block.
   *
   * Renders the content using the provided block plugin, and then:
   * - if there is no content, aborts rendering, and makes sure the block won't
   *   be rendered.
   * - if there is content, moves the contextual links from the block content to
   *   the block itself.
   */
  public static function preRender($build) {
    $content = $build['#block']->getPlugin()->build();
    // Remove the block entity from the render array, to ensure that blocks
    // can be rendered without the block config entity.
    unset($build['#block']);
    if ($content !== NULL && !Element::isEmpty($content)) {
      // Place the $content returned by the block plugin into a 'content' child
      // element, as a way to allow the plugin to have complete control of its
      // properties and rendering (for instance, its own #theme) without
      // conflicting with the properties used above, or alternate ones used by
      // alternate block rendering approaches in contrib (for instance, Panels).
      // However, the use of a child element is an implementation detail of this
      // particular block rendering approach. Semantically, the content returned
      // by the plugin "is the" block, and in particular, #attributes and
      // #contextual_links is information about the *entire* block. Therefore,
      // we must move these properties from $content and merge them into the
      // top-level element.
      foreach (array('#attributes', '#contextual_links') as $property) {
        if (isset($content[$property])) {
          $build[$property] += $content[$property];
          unset($content[$property]);
        }
      }
      $build['content'] = $content;
    }
    // Either the block's content is completely empty, or it consists only of
    // cacheability metadata.
    else {
      $manager = \Drupal::service('plugin.manager.menu.local_task');
      $links = $manager->getLocalTasks(\Drupal::routeMatch()->getRouteName(), 1);
      $cacheability = new CacheableMetadata();
      $cacheability = $cacheability->merge($links['cacheability']);
      $tabs = [
        '#theme' => 'menu_local_tasks',
      ];
      $tabs += [
        '#secondary' => count(Element::getVisibleChildren($links['tabs'])) > 0 ? $links['tabs'] : [],
      ];
      $content = [];
      $cacheability->applyTo($content);
      if (!empty($tabs['#secondary'])) {
        $content = $content + $tabs;
        foreach (array('#attributes', '#contextual_links') as $property) {
          if (isset($content[$property])) {
            $build[$property] += $content[$property];
            unset($content[$property]);
          }
        }
        $build['content'] = $content;
      }
      else {
        // Abort rendering: render as the empty string and ensure this block is
        // render cached, so we can avoid the work of having to repeatedly
        // determine whether the block is empty. For instance, modifying or adding
        // entities could cause the block to no longer be empty.
        $build = array(
          '#markup' => '',
          '#cache' => $build['#cache'],
        );
      }
      // If $content is not empty, then it contains cacheability metadata, and
      // we must merge it with the existing cacheability metadata. This allows
      // blocks to be empty, yet still bubble cacheability metadata, to indicate
      // why they are empty.
      if (!empty($content)) {
        CacheableMetadata::createFromRenderArray($build)
          ->merge(CacheableMetadata::createFromRenderArray($content))
          ->applyTo($build);
      }
    }
    return $build;
   }

}
