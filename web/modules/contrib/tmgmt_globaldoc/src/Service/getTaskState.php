<?php

namespace Drupal\tmgmt_globaldoc\Service;

class getTaskState
{

  /**
   * 
   * @var string $businessUnit
   * @access public
   */
  public $businessUnit;

  /**
   * 
   * @var string $requestorID
   * @access public
   */
  public $requestorID;

  /**
   * 
   * @var string $taskID
   * @access public
   */
  public $taskID;

  /**
   * 
   * @param string $businessUnit
   * @param string $requestorID
   * @param string $taskID
   * @access public
   */
  public function __construct($businessUnit, $requestorID, $taskID)
  {
    $this->businessUnit = $businessUnit;
    $this->requestorID = $requestorID;
    $this->taskID = $taskID;
  }

}
