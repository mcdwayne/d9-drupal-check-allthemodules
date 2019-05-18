<?php

namespace Drupal\ldadmin\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining JSON-LD Mapping entities.
 */
interface JsonLDMappingInterface extends ConfigEntityInterface {

  /**
   * Get Nid.
   */
  public function getNid();

  /**
   * Get Json.
   */
  public function getJson();

  /**
   * Set Nid.
   */
  public function setNid($nid);

  /**
   * Set Json.
   */
  public function setJson($json);

}
