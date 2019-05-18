<?php
/**
 * @file
 * Contains Drupal\schema\Migration\SchemaMigratorOptions.
 */

namespace Drupal\schema\Migration;


class SchemaMigratorOptions {
  public $useDifferentTables = TRUE;
  public $useSameTables = FALSE;

  public $fixTableComments = FALSE;

  public $addMissingColumns = FALSE;
  public $updateColumnProperties = FALSE;
  public $removeExtraColumns = FALSE;

  public $recreatePrimaryKey = FALSE;
  public $recreateIndexes = FALSE;
  public $recreateExtraIndexes = TRUE;

  /**
   * @param boolean $useDifferentTables
   * @return $this
   */
  public function setUseDifferentTables($useDifferentTables) {
    $this->useDifferentTables = $useDifferentTables;
    return $this;
  }

  /**
   * @param boolean $useSameTables
   * @return $this
   */
  public function setUseSameTables($useSameTables) {
    $this->useSameTables = $useSameTables;
    return $this;
  }


  /**
   * @param $fixTableComments
   * @return $this
   */
  public function setFixTableComments($fixTableComments) {
    $this->fixTableComments = $fixTableComments;
    return $this;
  }

  /**
   * @param $addMissingColumns
   * @return $this
   */
  public function setAddMissingColumns($addMissingColumns) {
    $this->addMissingColumns = $addMissingColumns;
    return $this;
  }

  /**
   * @param $removeExtraColumns
   * @return $this
   */
  public function setRemoveExtraColumns($removeExtraColumns) {
    $this->removeExtraColumns = $removeExtraColumns;
    return $this;
  }

  /**
   * @param $updateColumnProperties
   * @return $this
   */
  public function setUpdateColumnProperties($updateColumnProperties) {
    $this->updateColumnProperties = $updateColumnProperties;
    return $this;
  }

  /**
   * @param boolean $recreateIndexes
   * @return $this
   */
  public function setRecreateIndexes($recreateIndexes) {
    $this->recreateIndexes = $recreateIndexes;
    return $this;
  }

  /**
   * @param boolean $recreatePrimaryKey
   * @return $this
   */
  public function setRecreatePrimaryKey($recreatePrimaryKey) {
    $this->recreatePrimaryKey = $recreatePrimaryKey;
    return $this;
  }

  /**
   * @param $recreateExtraIndexes
   * @return $this
   */
  public function setRecreateExtraIndexes($recreateExtraIndexes) {
    $this->recreateExtraIndexes = $recreateExtraIndexes;
    return $this;
  }



}
