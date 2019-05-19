<?php

namespace Drupal\webform_epetition;

/**
 * Interface WebformEpetitionSearchInterface.
 */
interface WebformEpetitionSearchInterface {

  /**
   * @return mixed
   */
  public function getResults();

  /**
   * @param $queryParam
   *
   * @return mixed
   */
  public function setQueryParam($queryParam);

  /**
   * @param $dataType
   *
   * @return mixed
   */
  public function setDataType($dataType);

}
