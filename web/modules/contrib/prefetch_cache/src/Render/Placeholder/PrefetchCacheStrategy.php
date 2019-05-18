<?php

namespace Drupal\prefetch_cache\Render\Placeholder;

use Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface;
use Drupal\prefetch_cache\PrefetchCacheInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the PrefetchCache placeholder strategy.
 *
 * Turns off BigPipe for PrefetchCache requests.
 */
class PrefetchCacheStrategy implements PlaceholderStrategyInterface {

  /**
   * The decorated BigPipe placeholder strategy.
   *
   * @var \Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface
   */
  protected $bigPipeStrategy;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new PrefetchCacheStrategy class.
   *
   * @param \Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface $big_pipe_strategy
   *   The decorated BigPipe placeholder strategy.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(PlaceholderStrategyInterface $big_pipe_strategy, RequestStack $request_stack) {
    $this->bigPipeStrategy = $big_pipe_strategy;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function processPlaceholders(array $placeholders) {
    // PrefetchCache requests should not be processed by BigPipe, as the
    // PrefetchCache module doesn't expect streamed responses.
    $prefetch_cache_request = $this->requestStack->getCurrentRequest()->attributes->has(PrefetchCacheInterface::PREFETCH_CACHE_REQUEST);
    return $prefetch_cache_request ? [] : $this->bigPipeStrategy->processPlaceholders($placeholders);
  }

}
