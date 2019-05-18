<?php

/**
 * @file
 * Contains \Drupal\monster_menus\PathProcessor\OutboundPathProcessor.
 */

namespace Drupal\monster_menus\PathProcessor;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

class OutboundPathProcessor implements OutboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $_mm_custom_url_rewrite_outbound_cache = &drupal_static('_mm_custom_url_rewrite_outbound_cache', []);
    $original_path = $path;
    $path = ltrim($path, '/');

    $cache_id = $original_path . ':' . (isset($options['query']) && $options['query'] ? serialize($options['query']) : '');
    if (isset($_mm_custom_url_rewrite_outbound_cache[$cache_id])) {
      return $_mm_custom_url_rewrite_outbound_cache[$cache_id];
    }

    //    debug_add("** from: $path");
    if (($arg0 = mm_parse_args($mmtids, $oarg_list, $this_mmtid, $path)) == 'mm') {
      if ($mmtids && $mmtids[0] == mm_home_mmtid()) {
        if (count($mmtids) == 1 && count($oarg_list)) {
          return $_mm_custom_url_rewrite_outbound_cache[$cache_id] = "/$path";
        }
        array_shift($mmtids);
      }

      $test_path = "mm/$this_mmtid" . (isset($options['query']) && $options['query'] ? serialize($options['query']) : '');
      if (isset($_mm_custom_url_rewrite_outbound_cache[$test_path])) {
        $path = implode('/', array_merge([$_mm_custom_url_rewrite_outbound_cache[$test_path]], $oarg_list));
        return $_mm_custom_url_rewrite_outbound_cache[$cache_id] = "/$path";
      }

      $tree = mm_content_get($mmtids);

      foreach ($mmtids as $i => $mmtid) {
        foreach ($tree as $key => $item) {
          if ($item->mmtid == $mmtid) {
            if ($item->alias != '') {
              $mmtids[$i] = $item->alias;
            }
            unset($tree[$key]);
            break;
          }
        }
      }

      $path = implode('/', array_merge($mmtids, $oarg_list));

      mm_module_invoke_all_array('mm_url_rewrite_outbound', [
        $this_mmtid,
        &$path,
        &$options,
        $original_path,
      ]);
    }
    elseif ($path != '') {
      $curr_page = mm_active_menu_item();
      $link_page = mm_active_menu_item($path);

      $mmtid = isset($curr_page->mmtid) && !is_null($curr_page->nid) ? $curr_page->mmtid : (isset($link_page->mmtid) ? $link_page->mmtid : NULL);
      if ($mmtid && !is_null($link_page->nid)) {
        $path = implode('/', array_merge(['mm', $mmtid, $arg0], $oarg_list));
      }

      mm_module_invoke_all_array('mm_url_rewrite_outbound', [
        $mmtid,
        &$path,
        &$options,
        $original_path,
      ]);
    }

    $path = "/$path";
    // Don't use $cache_id here, since $options['query'] may have changed
    $_mm_custom_url_rewrite_outbound_cache[$original_path . ':' . (isset($options['query']) && $options['query'] ? serialize($options['query']) : '')] = $path;
    //    debug_add("** to: $path ");
    return $path;
  }

}
