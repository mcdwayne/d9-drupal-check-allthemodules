<?php

namespace Drupal\onepass;

use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines an interface for taxonomy_term entity storage classes.
 */
interface OnepassNodeStorageInterface extends ContentEntityStorageInterface {

  /**
   * Load OnepassNode entity by nid property.
   *
   * @param int $nid
   *   Related node nid.
   *
   * @return mixed
   *   OnepassNode entity on success or NULL otherwise.
   */
  public function loadByNid($nid);

  /**
   * Save relation node to Onepass service.
   *
   * @param int $nid
   *   Related node nid.
   */
  public function saveRelation($nid);

  /**
   * Delete relation node to Onepass service.
   *
   * @param int $nid
   *   Related node nid.
   */
  public function deleteRelation($nid);

}
