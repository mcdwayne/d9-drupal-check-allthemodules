<?php

namespace Drupal\drupal_yext\YextContent;

use Drupal\drupal_yext\traits\CommonUtilities;

/**
 * Represents a Node on the Yext API.
 */
class YextSourceRecord implements NodeMigrateSourceInterface {

  use CommonUtilities;

  /**
   * Constructor.
   *
   * @param array $structure
   *   A associative array structure from Yext.
   */
  public function __construct(array $structure) {
    $this->structure = $structure;
  }

  /**
   * {@inheritdoc}
   */
  public function getBio() : string {
    return $this->parseElem('string', ['description'], '');
  }

  /**
   * {@inheritdoc}
   */
  public function getGeo() : array {
    try {
      $lat = $this->parseElem('double', ['yextDisplayLat'], 0.0);
      $lon = $this->parseElem('double', ['yextDisplayLng'], 0.0);
      if (!$lon) {
        return [];
      }
      return [
        'lat' => $lat,
        'lon' => $lon,
      ];
    }
    catch (\Throwable $t) {
      $this->watchdogThrowable($t);
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCustom(string $id) : string {
    if (is_numeric($id)) {
      return $this->parseElem('string', ['customFields', $id], '', FALSE, '', ['cast-as-type' => TRUE]);
    }
    else {
      return $this->parseElem('string', explode('][', $id), '', FALSE, '', ['cast-as-type' => TRUE]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getHeadshot() : string {
    return $this->parseElem('string', ['headshot', 'url'], '');
  }

  /**
   * {@inheritdoc}
   */
  public function getName() : string {
    return $this->parseElem('string', ['locationName'], '');
  }

  /**
   * {@inheritdoc}
   */
  public function getYextId() : string {
    return $this->parseElem('string', ['id'], '', TRUE, 'The Node ID on Yext is required because that is how we track which Drupal nodes are linked to which nodes');
  }

  /**
   * {@inheritdoc}
   */
  public function getYextLastUpdate() : int {
    return $this->parseElem('integer', ['timestamp'], 0, TRUE, 'The last update (timestamp) field on Yext is required because that is how we track whether nodes are out of date on Drupal.');
  }

  /**
   * {@inheritdoc}
   */
  public function getYextRawData() : string {
    return json_encode($this->structure, TRUE);
  }

  /**
   * Wrapper around CommonUtilities::assocArrayElem() using our structure.
   */
  public function parseElem(string $type, array $keys, $default, bool $required = FALSE, $required_message = '', $options = []) : string {
    try {
      $return = $this->assocArrayElem($this->structure, $type, $keys, $default, $required, $required_message, $options);
    }
    catch (\Throwable $t) {
      $this->watchdogThrowable($t);
      $return = $default;
    }
    return $return;
  }

}
