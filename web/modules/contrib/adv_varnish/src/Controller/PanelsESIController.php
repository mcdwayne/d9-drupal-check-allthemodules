<?php

/**
 * @file
 * Contains \Drupal\adv_varnish\Controller\PanelsESIController.
 */

namespace Drupal\adv_varnish\Controller;

use Drupal\adv_varnish\Response\ESIResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\page_manager\Entity\Page;


class PanelsESIController extends ControllerBase {

  /**
   * Return rendered block html to replace esi tag.
   */
  public function content($page, $page_variant, $block_id){

    $response = new ESIResponse();
    $page = Page::load($page);
    $block = $page->getVariant($page_variant)->getVariantPlugin()->getBlock($block_id);
    $build = $block->build();
    $content = \Drupal::service('renderer')->renderPlain($build);
    $default = \Drupal::config('adv_varnish.settings');
    if ($block_id) {
      $block_conf = $default->get($block_id);
    }
    if ($block) {

      $ttl = $block_conf['cache']['max_age'];

      // Mark this block and response as rendered through ESI request.
      $block->_esi = 1;

      // Add block to dependency to respect block tags and ttl.
      $response->addCacheableDependency($block);
      $response->getCacheableMetadata()->setCacheMaxAge((int) $ttl);
    }

    $response->setContent($content);

    return $response;
  }

}

