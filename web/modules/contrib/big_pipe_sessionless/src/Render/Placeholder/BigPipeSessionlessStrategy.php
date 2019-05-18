<?php

namespace Drupal\big_pipe_sessionless\Render\Placeholder;

use Drupal\big_pipe\Render\Placeholder\BigPipeStrategy;

/**
 * Defines the BigPipe sessionless placeholder strategy, to send HTML in chunks.
 *
 * To avoid a potential no-JS redirect, no-session requests always use no-JS
 * BigPipe placeholders.
 *
 * @see \Drupal\big_pipe\Render\Placeholder\BigPipeStrategy
 *
 * This is reusing almost everything of BigPipeStrategy. The only differences:
 * 1. In ::processPlaceholders(), we invert the session-related logic: instead
 *    of ignoring requests without a session, we ignore requests with a session.
 *    We also only act on GET requests, because Symfony sets response's content
 *    to NULL on HEAD requests.
 * 2. ::doProcessPlaceholders() is made a lot simpler: since the goal is to
 *    accelerate Page Cache misses (and to hence cause Page Cache hits for
 *    subsequent requests), we can only use no-JS BigPipe placeholders.
 *    Otherwise we would not be able to create a HTML response to store in Page
 *    Cache.
 * 3. This placeholder strategy service has a priority that is LOWER than that
 *    of BigPipeStrategy. This ensures that BigPipeStrategy runs first. But it
 *    ignores sessionless requests, and since this handles only sessionless
 *    requests, they each have their own clear responsibility.
 *
 * @see placeholder_strategy.big_pipe_sessionless
 * @see big_pipe_sessionless.services.yml
 */
class BigPipeSessionlessStrategy extends BigPipeStrategy {

  /**
   * {@inheritdoc}
   */
  public function processPlaceholders(array $placeholders) {
    $request = $this->requestStack->getCurrentRequest();

    // Sessionless BigPipe only acts on GET requests. It cannot act on HEAD
    // requests, because \Symfony\Component\HttpFoundation\Response::prepare()
    // sets a response's content to NULL for HEAD requests, which means
    // \Drupal\big_pipe_sessionless\Render\BigPipeSessionless::sendContent() has
    // no content to prime the Page Cache with.
    if (!$request->isMethod('GET')) {
      return [];
    }

    // Routes can opt out from using the BigPipe HTML delivery technique.
    if ($this->routeMatch->getRouteObject()->getOption('_no_big_pipe')) {
      return [];
    }

    // @NOTE: We do exactly the opposite of parent::processPlaceholders().
    // everything else in this method is identical to the parent method.
    if ($this->sessionConfiguration->hasSession($request)) {
      return [];
    }

    return $this->doProcessPlaceholders($placeholders);
  }

  /**
   * Transforms placeholders to BigPipe placeholders, only no-JS.
   *
   * Only no-JS placeholders to allow BigPipe to accelerate Page Cache misses.
   *
   * @param array $placeholders
   *   The placeholders to process.
   *
   * @return array
   *   The BigPipe placeholders.
   */
  protected function doProcessPlaceholders(array $placeholders) {
    $overridden_placeholders = [];
    foreach ($placeholders as $placeholder => $placeholder_elements) {
      $overridden_placeholders[$placeholder] = static::createBigPipeNoJsPlaceholder($placeholder, $placeholder_elements, static::placeholderIsAttributeSafe($placeholder));
    }

    return $overridden_placeholders;
  }

}
