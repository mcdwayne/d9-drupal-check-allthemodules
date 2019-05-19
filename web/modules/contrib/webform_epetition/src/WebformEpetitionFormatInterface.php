<?php

namespace Drupal\webform_epetition;

/**
 * Interface WebformEpetitionFormatInterface.
 */
interface WebformEpetitionFormatInterface {

  /**
   * @param $response
   *
   * @return mixed
   */
  public function setResponse($response);

  /**
   * @param $dataType
   *
   * @return mixed
   */
  public function setDataType($dataType);

  /**
   * @return mixed
   */
  public function getDetails();

  /**
   * @return mixed
   */
  public function getEmails();

  /**
   * @return mixed
   */
  public function getNames();
}
