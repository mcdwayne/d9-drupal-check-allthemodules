<?php

namespace Drupal\tracking_number;

/**
 * A computed property for a tracking number's URL string.
 */
class UrlStringComputed extends UrlComputed {

  /**
   * Cached URL string.
   *
   * @var string|null
   */
  protected $urlString = NULL;

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    if ($this->urlString !== NULL) {
      return $this->urlString;
    }

    // Utilize our parent to populate a url property, from which we'll produce a
    // string.
    parent::getValue();
    if ($this->url !== NULL) {
      $this->urlString = $this->url->toString();
    }

    return $this->urlString;
  }

}
