<?php
/**
 * @file
 * Contains \Drupal\collect_test\TestResponseController.
 */

namespace Drupal\collect_test;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test response controller.
 */
class TestResponseController extends ControllerBase {

  /**
   * Returns response based on status code and Accept request header.
   */
  public function makeResponse($status_code) {
    $content = '<!DOCTYPE html><html><body><h1>Test body</h1><p>Test paragraph.</p></body></html>';
    $accept_header = \Drupal::requestStack()->getCurrentRequest()->headers->get('Accept');
    if ($accept_header == 'application/json') {
      $content = '{"headers": {"status_code": ' . $status_code . '}}';
    }
    return new Response($content, $status_code, ['Content-Type' => $accept_header]);
  }

  /**
   * Returns non UTF-8 encoded content.
   */
  public function makeNonUtf8Response($charset) {
    $content = '<!DOCTYPE html><html><body><h1>Test body</h1><p>Test paragraph - äëü. </p></body></html>';
    $content = iconv('UTF-8', $charset, $content);
    $content_type = \Drupal::requestStack()->getCurrentRequest()->headers->get('Accept');
    return new Response($content, 200, ['Content-Type' => $content_type]);
  }
}
