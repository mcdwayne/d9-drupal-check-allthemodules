<?php

namespace Drupal\oop_forms\Form\Element;

// Prevent naming clash with local Url class.
use Drupal\Core\Url as CoreUrl;

/**
 * Class Link
 * Provides a link render element.
 *
 */
class Link extends Element {

  /**
   * Url object containing URL information pointing to a internal or external link.
   *
   * @var CoreUrl
   */
  protected $url;

  /**
   * Link constructor.
   */
  public function __construct() {
    return parent::__construct('link');
  }

  /**
   * Gets Url object containing URL information pointing to a internal or external link.
   *
   * @return CoreUrl
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * Sets Url object containing URL information pointing to a internal or external link.
   *
   * @param CoreUrl $url
   *
   * @return Link
   */
  public function setUrl($url) {
    $this->url = $url;

    return $this;
  }

  /**
   * {@inheritdoc}.
   */
  public function build() {
    $form = parent::build();

    Element::addParameter($form, 'url', $this->url);

    return $form;
  }

}
