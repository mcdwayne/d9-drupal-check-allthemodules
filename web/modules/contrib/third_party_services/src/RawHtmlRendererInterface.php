<?php

namespace Drupal\third_party_services;

use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Render\HtmlResponseAttachmentsProcessor;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Component\Render\MarkupInterface;

/**
 * Render the markup as fully-qualified, standalone HTML.
 */
interface RawHtmlRendererInterface extends ContainerInjectionInterface {

  /**
   * RawHtmlRenderer constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Instance of the "renderer" service.
   * @param \Drupal\Core\Render\HtmlResponseAttachmentsProcessor $response_attachments_processor
   *   Instance of the "html_response.attachments_processor" service.
   */
  public function __construct(RendererInterface $renderer, HtmlResponseAttachmentsProcessor $response_attachments_processor);

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Render\RendererInterface::renderRoot()
   */
  public function renderRoot(&$elements): MarkupInterface;

  /**
   * Returns standalone piece of HTML.
   *
   * @param array $content
   *   Renderable structure to transform.
   *
   * @return \Drupal\Core\Render\HtmlResponse
   *   HTML response
   *
   * @see template_preprocess_html()
   */
  public function produceResponse(array &$content): HtmlResponse;

}
