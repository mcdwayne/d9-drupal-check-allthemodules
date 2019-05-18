<?php

namespace Drupal\cacheability_metadata_inspector;

use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RenderContext;

class CacheabilityMetadataAwareRenderContext extends RenderContext {

  /**
   * Factory method.
   *
   * @param \Drupal\Core\Render\RenderContext $render_context
   *   Context to decorate.
   *
   * @return static
   *   New instance.
   */
  public static function fromRenderContext(RenderContext $render_context) {
    $context = new static();
    while ($render_context->count()) {
      $context->push($render_context->shift());
    }
    return $context;
  }

  /**
   * Update the element with the context.
   *
   * @param $element
   */
  public function update(&$element) {
    parent::update($element);
    if (empty($element['#markup'])) {
      return;
    }
    $markup = $element['#markup'];
    if (is_string($element['#markup'])) {
      $markup = Markup::create($element['#markup']);
    }
    $hash = spl_object_hash($markup);
    $element['#markup'] = Markup::create(sprintf("<!-- cache-data-%s-start \ntags:\n-%s\n\ncontexts:\n-%s-->%s<!-- cache-data-%s-end -->", $hash, implode("\n-", $element['#cache']['tags']), implode("\n-", $element['#cache']['contexts']),$markup->__toString(), $hash));
  }

  /**
   * Applies self to a render context.
   */
  public function applyToRenderContext(RenderContext $render_context) {
    while ($this->count()) {
      $render_context->push($this->shift());
    }
  }

}
