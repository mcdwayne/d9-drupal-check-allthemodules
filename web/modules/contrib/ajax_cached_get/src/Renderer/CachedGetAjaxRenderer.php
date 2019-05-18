<?php

namespace Drupal\ajax_cached_get\Renderer;

use Drupal\ajax_cached_get\Response\GetAjaxResponse;
use Drupal\Core\Render\MainContent\AjaxRenderer;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Render\RenderCacheInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Cache\Cache;


class CachedGetAjaxRenderer extends AjaxRenderer{
  
  /**
   * The render cache service.
   *
   * @var \Drupal\Core\Render\RenderCacheInterface
   */
  protected $renderCache;
  
  /**
   * The renderer configuration array.
   *
   * @see sites/default/default.services.yml
   *
   * @var array
   */
  protected $rendererConfig;
  
  public function __construct(ElementInfoManagerInterface $element_info_manager, RenderCacheInterface $render_cache, array $renderer_config) {
    parent::__construct($element_info_manager);
    $this->renderCache = $render_cache;
    $this->rendererConfig = $renderer_config;
  }
  
  public function renderResponse(array $main_content, Request $request, RouteMatchInterface $route_match) {
    $response = new GetAjaxResponse();
    
    if (isset($main_content['#type']) && ($main_content['#type'] == 'ajax')) {
      // Complex Ajax callbacks can return a result that contains an error
      // message or a specific set of commands to send to the browser.
      $main_content += $this->elementInfoManager->getInfo('ajax');
      $error = $main_content['#error'];
      if (!empty($error)) {
        // Fall back to some default message otherwise use the specific one.
        if (!is_string($error)) {
          $error = 'An error occurred while handling the request: The server received invalid input.';
        }
        $response->addCommand(new AlertCommand($error));
      }
    }
    
    $html = $this->drupalRenderRoot($main_content);
    $response->setAttachments($main_content['#attached']);
    
    // get cache tags
    $content = $this->renderCache->getCacheableRenderArray($main_content);
    $content['#cache']['contexts'] = Cache::mergeContexts($content['#cache']['contexts'], $this->rendererConfig['required_cache_contexts']);
    $response->addCacheableDependency(CacheableMetadata::createFromRenderArray($content));
  
    // The selector for the insert command is NULL as the new content will
    // replace the element making the Ajax call. The default 'replaceWith'
    // behavior can be changed with #ajax['method'].
    $response->addCommand(new InsertCommand(NULL, $html));
    $status_messages = array('#type' => 'status_messages');
    $output = $this->drupalRenderRoot($status_messages);
    if (!empty($output)) {
      $response->addCommand(new PrependCommand(NULL, $output));
    }
    return $response;
  }
  
  
}