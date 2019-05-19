<?php
/**
 * @file
 * Contains \Drupal\wisski_pathbuilder\WisskiPathInterface
 */

namespace Drupal\wisski_pathbuilder;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a pathbuilder path entity type.
 */
interface WisskiPathInterface extends ConfigEntityInterface {
  public function getID();
  public function getName();
  public function getPathArray();
  public function getDatatypeProperty();
  public function getShortName();
  public function getDisamb();
  public function getLength();
  public function getDescription(); 
  public function isGroup();
  public function getType();
#  public function getWeight();  
}
