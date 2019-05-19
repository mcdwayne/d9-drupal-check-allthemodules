<?php

namespace Drupal\visualn_block\IFrameContent;

use Drupal\visualn_iframe\IFrameContentProvider\ContentProviderInterface;
use Drupal\block\Entity\Block;
use Drupal\visualn_block\Plugin\Block\VisualNBlock;
use Drupal\visualn\Manager\DrawingFetcherManager;

/**
 * Provides content for VisualN blocks iframes.
 *
 * @ingroup iframes_toolkit
 */
class BlockIFrameContentProvider implements ContentProviderInterface {

  /**
   * The visualn drawing fetcher manager service.
   *
   * @var \Drupal\visualn\Manager\DrawingFetcherManager
   */
  protected $visualNDrawingFetcherManager;

  /**
   * Constructs a IFrameContentProvider service object.
   *
   * @param \Drupal\visualn\Manager\DrawingFetcherManager $visualn_drawing_fetcher_manager
   *   The visualn drawing fetcher manager service.
   */
  public function __construct(DrawingFetcherManager $visualn_drawing_fetcher_manager) {
    $this->visualNDrawingFetcherManager = $visualn_drawing_fetcher_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($handler_key, $data, $settings) {
    // @todo: data contains fetcher_id and fetcher_config,
    //   block complete configruation or its part may be also required
    //   e.g. to test user permissions for the block

    // @todo: check configuration here (if needed)
    // @todo: maybe set handler_key in .services.yml file as it is done for ThemeNegotiator
    //    so that other modules content providers could use the current IFrameContentProvider class
    if ($handler_key == VisualNBlock::IFRAME_HANDLER_KEY && !empty($data)) {
      return TRUE;
    }
    else {
      return FALSE;
    }


    // @todo: check content provider service, do not cache if provider not found (?)
  }

  /**
   * {@inheritdoc}
   */
  public function provideContent($handler_key, $data, $settings) {
    // @todo: data contains fetcher_id and fetcher_config,
    //   block complete configruation or its part may be also required
    //   e.g. to test user permissions for the block

    if (!empty($data)) {
      // @todo: also if empty data, nothing to render
    }
    else {
      // @todo: a block could be imported via configuration to another site,
      //   should be a way to automatically create visualn_iframe entries for this case
      //   maybe on config import or add a drush command
    }

    // @todo: see https://drupal.stackexchange.com/questions/171686/how-can-i-programmatically-display-a-block

    $fetcher_id = $data['fetcher_id'];
    // @todo: should be array
    $fetcher_config = $data['fetcher_config'];
    if ($fetcher_id) {
      $fetcher_plugin = $this->visualNDrawingFetcherManager->createInstance($fetcher_id, $fetcher_config);
      // add window_parameters (width and height) support to iframes
      // @todo: @see comments in \Drupal\visualn_embed\IFrameContent\EmbeddedDrawingIFrameContentProvider::provideContent()
      $query_parameters =  \Drupal::request()->query->all();
      $width = !empty($query_parameters['width']) ? $query_parameters['width'] : '';
      $height = !empty($query_parameters['height']) ? $query_parameters['height'] : '';
      $window_parameters = ['width' => $width, 'height' => $height];
      $fetcher_plugin->setWindowParameters($window_parameters);
      $render = $fetcher_plugin->fetchDrawing();
      // visualn_iframe specific cache tags are set in IFrameController::build()
      $render['#cache']['contexts'][] = 'visualn_iframe_drawing_window_parameters';
    }
    else {
      // @todo: use some default content
      //   add cache tags or disable cache
      $render = [
        '#markup' => 'record not found',
      ];
    }

    return $render;

    // @todo: how to track permissions (also for drawing entities iframes)?
    //   e.g. we want to show iframes only on 'specific' sites that are authenticated
    //   by some way (though seems no way with iframes browsers support)
    //   or maybe to show iframes only for authenticated user, otherwise show empty content
    //   on third-party sites
    //   on block save add block_id (i.e. entity id) to the respecting column of visualn_iframe
    //   though should be of 'string' type then


    $block_id = $configuration['id'];
    $block_manager = \Drupal::service('plugin.manager.block');
    // @todo: get block config
    //$plugin_block = $block_manager->createInstance('visualn_block', $configuration);
    $plugin_block = $block_manager->createInstance($block_id, $configuration);
    // Some blocks might implement access check.
    $access_result = $plugin_block->access(\Drupal::currentUser());
    // Return empty render array if user doesn't have access.
    // $access_result can be boolean or an AccessResult class
    if (is_object($access_result) && $access_result->isForbidden() || is_bool($access_result) && !$access_result) {
      // You might need to add some cache tags/contexts.
      return [];
    }
    // @todo: add cache tags
    $render = $plugin_block->build();
    // do not add share link for embedded content
    unset($render['share_iframe_link']);
    return $render;
  }

}
