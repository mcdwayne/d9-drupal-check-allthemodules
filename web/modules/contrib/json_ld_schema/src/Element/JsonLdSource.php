<?php

namespace Drupal\json_ld_schema\Element;

use Drupal\Core\Render\Element\HtmlTag;
use Drupal\Core\Render\Element\RenderElement;

/**
 * A JSON LD source element.
 *
 * @RenderElement("json_ld_source")
 */
class JsonLdSource extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#tag' => 'script',
      '#attributes' => ['type' => 'application/ld+json'],
      '#pre_render' => [
        [static::class, 'preRenderGetData'],
        [HtmlTag::class, 'preRenderHtmlTag'],
      ],

    ];
  }

  /**
   * Pre-render the JSON LD script.
   */
  public static function preRenderGetData($element) {
    // Add the possibly expensive getData call into a pre render function so
    // that it is cached by render cache.
    $source_manager = \Drupal::service('plugin.manager.json_ld_schema.source');
    $plugin = $source_manager->createInstance($element['#plugin_id']);
    $element['#value'] = json_encode($plugin->getData()->toArray(), JSON_UNESCAPED_UNICODE);
    return $element;
  }

}
