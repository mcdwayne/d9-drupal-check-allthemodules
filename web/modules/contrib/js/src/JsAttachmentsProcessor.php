<?php

namespace Drupal\js;

use Drupal\Core\Ajax\AjaxResponseAttachmentsProcessor;
use Drupal\Core\Render\AttachmentsInterface;

/**
 * Processes attachments of AJAX responses.
 *
 * @see \Drupal\Core\Ajax\AjaxResponse
 * @see \Drupal\Core\Render\MainContent\AjaxRenderer
 */
class JsAttachmentsProcessor extends AjaxResponseAttachmentsProcessor {

  /**
   * {@inheritdoc}
   */
  public function processAttachments(AttachmentsInterface $response) {
    assert('$response instanceof \\Drupal\\js\\JsResponse');

    /** @var \Drupal\js\JsResponse $response */
    $data = $response->getData();
    if (!isset($data['commands'])) {
      $data['commands'] = [];
    }
    $data['commands'] = array_merge($data['commands'], $this->buildAttachmentsCommands($response, $this->requestStack->getCurrentRequest()));
    $response->setData($data);

    return $response;
  }

}
