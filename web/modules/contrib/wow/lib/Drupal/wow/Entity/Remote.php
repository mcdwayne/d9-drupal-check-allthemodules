<?php

/**
 * @file
 * Definition of Drupal\wow\Entity\Remote.
 */

namespace Drupal\wow\Entity;

/**
 * Defines a remote entity class.
 */
abstract class Remote extends \Entity {

  /**
   * Time stamp for entity's last fetch.
   *
   * @var integer
   */
  public $lastFetched = 0;

  /**
   * Timestamp for entity's last update.
   *
   * @var integer
   */
  public $lastModified = 0;

  /**
   * The entity region.
   *
   * @var string
   */
  public $region;

  /**
   * The entity language.
   *
   * @var string
   */
  public $language = LANGUAGE_NONE;

  /**
   * Refresh this remote entity from the service.
   *
   * @return \Drupal\wow\Response
   *   A Response object in case of HTTP status 200 or 304.
   *
   * @throws \Drupal\wow\ResponseException
   *   A ResponseException in case of HTTP status 500 or 404.
   */
  public function refresh(array $fields = array()) {
    return wow_service_controller($this->entityType)->refresh($this);
  }

}
