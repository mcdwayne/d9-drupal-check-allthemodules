<?php

namespace Drupal\multiversion\Entity\Query;

use Drupal\Core\Entity\Query\QueryInterface as CoreQueryInterface;

interface QueryInterface extends CoreQueryInterface {

  /**
   * @param string $id
   * @return \Drupal\multiversion\Entity\Query\QueryInterface
   */
  public function useWorkspace($id);

  /**
   * @return \Drupal\multiversion\Entity\Query\QueryInterface
   */
  public function isDeleted();

  /**
   * @return \Drupal\multiversion\Entity\Query\QueryInterface
   */
  public function isNotDeleted();

}
