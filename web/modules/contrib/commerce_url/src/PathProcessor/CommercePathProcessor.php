<?php

namespace Drupal\commerce_url\PathProcessor;

use Drupal\commerce_url\EncryptDecrypt;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;

/**
 * Process the Inbound and Outbound checkout urls.
 */
class CommercePathProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if ($request instanceof Request) {
      //Explode the current path
      $arg = explode('/', $path);
      if ($arg[1] == "checkout"  && !(is_numeric($arg[2]))) {
        $path = $this->commerceUrlProcess($path, $arg, EncryptDecrypt::DECRYPT);
      }
    }
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if ($request instanceof Request) {
      //Explode the current path
      $arg = explode('/', $path);
      if ($arg[1] == "checkout"  && !(is_numeric($arg[2]))) {
        $path = $this->commerceUrlProcess($path, $arg, EncryptDecrypt::ENCRYPT);
      }
    }
    return $path;
  }

  /**
   * Helper function for processing the url.
   *
   * @param string $path
   *   The Path.
   * @param array $arg
   *   The exploded current path arguments.
   * @param string $crypt
   *   The cryptography type.
   *
   * @return string $path
   *   The processed path.
   */
  protected function commerceUrlProcess($path, array $arg, $crypt) {
    $order_id = $arg[2];
    // If path matches with URL.
    if (preg_match('/\/checkout\/[a-zA-Z0-9]+\/([a-zA-Z_]+)/i', $path, $matches)) {
      $step = $matches['1'];
      // Encrypt the ordr id.
      $encrypted = \Drupal::service('commerce_url.encrypt_decrypt')->customEncryptDecrypt($order_id, $crypt);
      // Convert path with hash value.
      $path = '/checkout/' . $encrypted . '/' . $step;
    }
    return $path;
  }

}
