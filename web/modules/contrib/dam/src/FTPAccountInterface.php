<?php

namespace Drupal\dam;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining a FTPAccount entity.
 */
 interface FTPAccountInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface {

   /**
   * Denotes that the ftp account is not active.
   */
   const NOT_PUBLISHED = 0;

   /**
   * Denotes that the ftp account is active.
   */
   const PUBLISHED = 1;

   /**
    * Gets the FTPAccount type.
    *
    * @return string
    *   The FTPAccount type.
    */
   public function getType();

   /**
    * Gets the FTPAccount title.
    *
    * @return string
    *   Title of the FTPAccount or account name.
    */
   public function getTitle();

   /**
    * Sets the FTPAccount name.
    *
    * @param string $title
    *   The node title.
    *
    * @return \Drupal\dam\FTPAccountInterface
    *   The called FTPAccount entity.
    */
   public function setTitle($title);

   /**
    * Gets the FTPAccount creation timestamp.
    *
    * @return int
    *   Creation timestamp of the FTPAccount.
    */
   public function getCreatedTime();

   /**
    * Sets the FTPAccount creation timestamp.
    *
    * @param int $timestamp
    *   The node creation timestamp.
    *
    * @return \Drupal\dam\FTPAccountInterface
    *   The called FTPAccount entity.
    */
   public function setCreatedTime($timestamp);

 }
