<?php

namespace Drupal\cacheability_metadata_inspector;

use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\Renderer;

/**
 * Defines a renderer that outputs cacheability metadata in html comments.
 */
class CacheabilityMetadataRenderer extends Renderer {

  /**
   * Sets the current render context.
   *
   * @param \Drupal\Core\Render\RenderContext|null $context
   *   The render context. This can be NULL for instance when restoring the
   *   original render context, which is in fact NULL.
   *
   * @return $this
   */
  protected function setCurrentRenderContext(RenderContext $context = NULL) {
    $request = $this->requestStack->getCurrentRequest();
    if ($context && !($context instanceof CacheabilityMetadataAwareRenderContext)) {
      $context = CacheabilityMetadataAwareRenderContext::fromRenderContext($context);
    }
    static::$contextCollection[$request] = $context;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function executeInRenderContext(RenderContext $context, callable $callable) {
    // Store the current render context.
    $previous_context = $this->getCurrentRenderContext();

    // Set the provided context and call the callable, it will use that context.
    $this->setCurrentRenderContext($context);
    $result = $callable();

    $current = $this->getCurrentRenderContext();
    if ($current instanceof CacheabilityMetadataAwareRenderContext) {
      $current->applyToRenderContext($context);
    }
    // @todo Convert to an assertion in https://www.drupal.org/node/2408013
    if ($context->count() > 1) {
      throw new \LogicException('Bubbling failed.');
    }

    // Restore the original render context.
    $this->setCurrentRenderContext($previous_context);

    return $result;
  }

}
