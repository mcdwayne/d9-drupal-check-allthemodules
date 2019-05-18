<?php

namespace Drupal\tmgmt_globaldoc\Service;

class getSourceTask
{

  /**
   * 
   * @var string $businessUnit
   * @access public
   */
  public $businessUnit;

  /**
   * 
   * @var string $requestorId
   * @access public
   */
  public $requestorId;

  /**
   * 
   * @var string $taskId
   * @access public
   */
  public $taskId;

  /**
   * 
   * @param string $businessUnit
   * @param string $requestorId
   * @param string $taskId
   * @access public
   */
  public function __construct($businessUnit, $requestorId, $taskId)
  {
    $this->businessUnit = $businessUnit;
    $this->requestorId = $requestorId;
    $this->taskId = $taskId;
  }

}
