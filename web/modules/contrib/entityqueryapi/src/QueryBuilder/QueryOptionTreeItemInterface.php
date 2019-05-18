<?php

namespace Drupal\entityqueryapi\QueryBuilder;

interface QueryOptionTreeItemInterface {

  /**
   * Insert the child into this object or one if its children objects.
   *
   * @param string $target_id
   *   The QueryOption id of the intended parent.
   * @param Drupal\entityqueryapi\QueryBuilder\QueryOptionInterface $option
   *   The QueryOption to insert.
   *
   * @return bool
   *  Whether or not the QueryOption could be inserted.
   */
  public function insert($target_id, QueryOptionInterface $option);

  /**
   * Returns whether or the given id is a (grand)child of the object.
   */
  public function hasChild($id);

}
