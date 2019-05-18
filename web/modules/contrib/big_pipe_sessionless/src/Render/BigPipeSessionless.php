<?php

namespace Drupal\big_pipe_sessionless\Render;

use Drupal\big_pipe\Render\BigPipe;
use Drupal\big_pipe\Render\BigPipeResponse;
use Drupal\Core\Render\HtmlResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * The sessionless BigPipe service.
 */
class BigPipeSessionless extends BigPipe {

  /**
   * The final HTML response.
   *
   * Contains replaced placeholders. Its cacheability metadata and attachments
   * are only for the placeholders.
   *
   * @var \Drupal\Core\Render\HtmlResponse
   *
   * @see \Drupal\big_pipe\Render\BigPipeResponse::getOriginalHtmlResponse()
   * @see ::sendContent()
   */
  protected $finalHtmlResponse;

  /**
   * The PageCache middleware.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $pageCacheMiddleware;

  /**
   * Sets the PageCache middleware.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $page_cache_middleware
   *   The page cache middleware.
   */
  public function setPageCacheMiddleware(HttpKernelInterface $page_cache_middleware) {
    $this->pageCacheMiddleware = $page_cache_middleware;
  }

  /**
   * {@inheritdoc}
   */
  protected function performPreSendTasks() {
    // Nothing to do.
  }

  /**
   * {@inheritdoc}
   */
  protected function performPostSendTasks() {
    // Nothing to do.
  }

  /**
   * {@inheritdoc}
   */
  public function sendContent(BigPipeResponse $response) {
    $this->finalHtmlResponse = new HtmlResponse();
    parent::sendContent($response);

    $this->primePageCache($response);

    // Don't keep around any state.
    $this->finalHtmlResponse = NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function sendChunk($chunk) {
    // First: send.
    parent::sendChunk($chunk);

    // Then: track every sent chunk.
    // @see ::sendContent()
    if ($chunk instanceof HtmlResponse) {
      $this->finalHtmlResponse->setContent($this->finalHtmlResponse->getContent() . $chunk->getContent());
      $this->finalHtmlResponse->addCacheableDependency($chunk->getCacheableMetadata());
      $this->finalHtmlResponse->addAttachments($chunk->getAttachments());
    }
    else {
      $this->finalHtmlResponse->setContent($this->finalHtmlResponse->getContent() . $chunk);
    }
  }

  /**
   * Primes the Page Cache based on the streamed response.
   *
   * @param \Drupal\big_pipe\Render\BigPipeResponse $response
   *   The BigPipe response that was sent.
   */
  protected function primePageCache(BigPipeResponse $response) {
    // Start with the original HTML response, so we have the appropriate meta-
    // data like headers, HTTP version, and so on.
    $streamed_response = $response->getOriginalHtmlResponse();

    // Override content with final HTML content (with replaced placeholders).
    $streamed_response->setContent($this->finalHtmlResponse->getContent());

    // Add any additional cacheability metadata for rendered placeholders.
    $streamed_response->addCacheableDependency($this->finalHtmlResponse->getCacheableMetadata());

    // Overwrite with final attachments (overwrite, not add, because attachments
    // need to be processed, and once the response is sent, they are processed;
    // if we would not overwrite, then we'd reprocess them).
    // @see \Drupal\Core\Render\AttachmentsResponseProcessorInterface
    $streamed_response->setAttachments($this->finalHtmlResponse->getAttachments());

    // Dispatch the KernelEvents::RESPONSE event, to let those event subscribers
    // do what they need to do. This is f.e. necessary for the cache tags header
    // for debugging purposes and for modules that integrate with reverse
    // proxies that support cache tags.
    $fake_request = $this->requestStack->getMasterRequest()->duplicate();
    $streamed_response = $this->filterResponse($fake_request, HttpKernelInterface::MASTER_REQUEST, $streamed_response);

    // Prime Page Cache.
    // @see \Drupal\big_pipe_sessionless\StackMiddleware\BigPipeSessionlessPageCache
    $this->pageCacheMiddleware->_storeResponse($this->requestStack->getCurrentRequest(), $streamed_response);
  }

}
