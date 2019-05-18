<?php

namespace Drupal\consent_iframe\Response;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class ConsentIframeResponseHeaderBag.
 *
 * @internal
 */
final class ConsentIframeResponseHeaderBag extends ResponseHeaderBag {

  /**
   * {@inheritdoc}
   */
  public function set($key, $values, $replace = TRUE) {
    if (!$replace && ($key === 'X-Frame-Options') && $this->has('Access-Control-Allow-Origin')) {
      // This response is for an allowed / whitelisted cross-origin request.
      // Therefore, do not add an X-Frame-Options restriction.
      return;
    }

    switch ($key) {
      case 'Cache-Control':
        if ($this->has('Cache-Control')) {
          // Cache-Control is already being set initially, ignore subsequent changes.
          return;
        }
        break;
      case 'Expires':
        return;
      case 'Vary':
      case 'Etag':
      case 'ETag':
      case 'Last-Modified':
        if (is_null($values) && $this->has('Cache-Control') && (strpos($this->get('Cache-Control'), 'public') !== FALSE)) {
          return;
        }
      break;
    }
    return parent::set($key, $values, $replace);
  }

  /**
   * {@inheritdoc}
   */
  public function remove($key) {
    switch ($key) {
      case 'Vary':
      case 'Etag':
      case 'ETag':
      case 'Last-Modified':
        if ($this->has('Cache-Control') && (strpos($this->get('Cache-Control'), 'public') !== FALSE)) {
          return;
        }
      break;
    }
    return parent::remove($key);
  }

}
