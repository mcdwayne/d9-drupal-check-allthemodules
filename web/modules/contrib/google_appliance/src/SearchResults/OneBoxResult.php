<?php

namespace Drupal\google_appliance\SearchResults;

/**
 * Defines a value object for a one-box result.
 */
class OneBoxResult {

  /**
   * Array of field values keyed by field name.
   *
   * @var string[]
   */
  protected $fields = [];

  /**
   * Absolute URL.
   *
   * @var string
   */
  protected $absoluteUrl;

  /**
   * Title.
   *
   * @var string
   */
  protected $title;

  /**
   * Constructs a new OneBoxResult object.
   *
   * @param string $absoluteUrl
   *   Absolute URL.
   * @param string $title
   *   Title.
   */
  public function __construct($absoluteUrl, $title) {
    $this->absoluteUrl = $absoluteUrl;
    $this->title = $title;
  }

  /**
   * Add field value.
   *
   * @param string $name
   *   Field name.
   * @param string $value
   *   Field value.
   *
   * @return $this
   */
  public function addFieldValue($name, $value) {
    $this->fields[$name] = $value;
    return $this;
  }

}
