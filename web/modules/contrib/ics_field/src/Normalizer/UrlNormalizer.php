<?php

namespace Drupal\ics_field\Normalizer;

class UrlNormalizer implements UrlNormalizerInterface {

  /**
   * Some rudimentary URL parsing.
   *
   * Processes a URL to make sure it has a canonical form,
   * e.g. protocol://some.domain.name.
   * There are much more complex regexes for this, but there
   * is little gain, since the iCal RFC itself does not require
   * valid URLs in this field.
   *
   * @link http://www.kanzaki.com/docs/ical/url.html
   * @link https://mathiasbynens.be/demo/url-regex
   *
   * @param string $url
   * @param string $scheme
   * @param string $schemaAndHttpHost
   *
   * @return null|string A normalized URL string or null otherwise.
   */
  public function normalize($url, $scheme, $schemaAndHttpHost) {

    if (!is_string($url) || $url === '') {
      return NULL;
    }

    $url = strip_tags($url);
    // Check if a URL_SCHEME is part of the $url
    // If it's missing, we will try to add it.
    if (empty(parse_url($url, PHP_URL_SCHEME))) {
      // Check to see if the $url consists of 2 or 3 parts
      // e.g. 2parts => website.com, 3parts => my.website.com
      // like a web-address without a protocol would look like.
      // *-parts URLs are also covered.
      if (preg_match('#^(\w+\.)+\w+(/?|(/.*))$#', $url)) {
        $url = $scheme . '://' . $url;
      } // This must be an internal path since it doesn't look like a web-address.
      else {
        $url = $schemaAndHttpHost .
               (strpos($url, '/', 0) === 0 ? '' : '/') . $url;
      }
    }
    return $url;
  }

}
