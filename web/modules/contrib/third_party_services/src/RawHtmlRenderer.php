<?php

namespace Drupal\third_party_services;

use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Render\HtmlResponseAttachmentsProcessor;
use Drupal\Component\Render\MarkupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Basic implementation of RawHtmlRendererInterface.
 */
class RawHtmlRenderer implements RawHtmlRendererInterface {

  /**
   * Instance of the "renderer" service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;
  /**
   * HTML response.
   *
   * @var \Drupal\Core\Render\HtmlResponse
   */
  protected $htmlResponse;
  /**
   * Instance of the "html_response.attachments_processor" service.
   *
   * @var \Drupal\Core\Render\HtmlResponseAttachmentsProcessor
   */
  protected $responseAttachmentsProcessor;

  /**
   * {@inheritdoc}
   */
  public function __construct(RendererInterface $renderer, HtmlResponseAttachmentsProcessor $response_attachments_processor) {
    $this->renderer = $renderer;
    $this->htmlResponse = new HtmlResponse();
    $this->responseAttachmentsProcessor = $response_attachments_processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static($container->get('renderer'), $container->get('html_response.attachments_processor'));
  }

  /**
   * {@inheritdoc}
   */
  public function renderRoot(&$elements): MarkupInterface {
    return $this->renderer->renderRoot($elements);
  }

  /**
   * {@inheritdoc}
   */
  public function produceResponse(array &$content): HtmlResponse {
    $content['#markup'] = $this->renderRoot($content);
    $content['#markup'] = "<styles>{$content['#markup']}<scripts><scripts_bottom>";

    foreach (['styles', 'scripts', 'scripts_bottom'] as $type) {
      $content['#attached']['html_response_attachment_placeholders'][$type] = "<{$type}>";
    }

    return $this->responseAttachmentsProcessor->processAttachments($this->htmlResponse->setContent($content));
  }

}
