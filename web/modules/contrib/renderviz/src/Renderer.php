<?php

/**
 * @file
 * Contains \Drupal\renderviz\Renderer.
 */

namespace Drupal\renderviz;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\Renderer as CoreRenderer;

/**
 * Decorates core's Renderer to provide the necessary metadata to renderviz' JS.
 */
class Renderer extends CoreRenderer {

  /**
   * {@inheritdoc}
   */
  protected function doRender(&$elements, $is_root_call = FALSE) {
    $original_elements = [];
    $original_elements['#cache'] = isset($elements['#cache']) ? $elements['#cache'] : [];

    $result = parent::doRender($elements, $is_root_call);

    // When there is no output, there is also nothing to visualize.
    if ($result === '') {
      return '';
    }

    // The HTML spec says HTML comments are markup, thus it's not allowed to use
    // HTML comments inside HTML element attributes. For example:
    //   <a <!--title="need to be comment out"-->>a link</a>
    // is as wrong as
    //  <a <span></span>>a link</a>
    // So we only wrap $result in a HTML comment with renderviz metadata when
    // $result actually contains HTML markup. So, 'This is text.' will cause an
    // early return, but 'This is text and <a href="…">a link</a>.' will not.
    // The presence of HTML indicates it's valid to have HTML, and hwen it
    // valid to have HTML, HTML comments are allowed too.
    if ($result == strip_tags($result)) {
      // Returned without debug output.
      return $result;
    }

    // Apply the same default cacheability logic that Renderer::doRender()
    // applies.
    $pre_bubbling_elements = $original_elements;
    $pre_bubbling_elements['#cache']['tags'] = isset($original_elements['#cache']['tags']) ? $original_elements['#cache']['tags'] : array();
    $pre_bubbling_elements['#cache']['max-age'] = isset($original_elements['#cache']['max-age']) ? $original_elements['#cache']['max-age'] : Cache::PERMANENT;
    // @todo Add these always? That's more accurate for visualization purposes;
    //   it is only for performance optimization purposes that the wrapped
    //   function doesn't do that.
    if ($is_root_call || isset($elements['#cache']['keys'])) {
      $required_cache_contexts = $this->rendererConfig['required_cache_contexts'];
      if (isset($pre_bubbling_elements['#cache']['contexts'])) {
        $pre_bubbling_elements['#cache']['contexts'] = Cache::mergeContexts($pre_bubbling_elements['#cache']['contexts'], $required_cache_contexts);
      }
      else {
        $pre_bubbling_elements['#cache']['contexts'] = $required_cache_contexts;
      }
    }

    // Add debug output.
    // @todo: This currently prints the final and pre-bubbling elements.
    $interesting_keys = ['keys', 'contexts', 'tags', 'max-age'];
    if (array_intersect(array_keys($elements['#cache']), $interesting_keys) || array_intersect(array_keys($pre_bubbling_elements['#cache']), $interesting_keys)) {
      $prefix = '<!--RENDERER_START-->' . '<!--' . Json::encode($elements['#cache']) .  '-->' . '<!--' . Json::encode($pre_bubbling_elements['#cache']) .  '-->';
      $suffix = '<!--RENDERER_END-->';
      $elements['#markup'] = Markup::create($prefix . $elements['#markup'] . $suffix);
    }

    return $elements['#markup'];
  }

}
