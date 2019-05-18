<?php

namespace Drupal\tmgmt_globaldoc\Service;

class submitSourceTask
{

  /**
   * 
   * @var string $businessUnit
   * @access public
   */
  public $businessUnit;

  /**
   * 
   * @var string $ibmDocId
   * @access public
   */
  public $ibmDocId;

  /**
   * 
   * @var string $taskId
   * @access public
   */
  public $taskId;

  /**
   * 
   * @var string $requestorId
   * @access public
   */
  public $requestorId;

  /**
   * 
   * @var string $transType
   * @access public
   */
  public $transType;

  /**
   * 
   * @var base64Binary $b
   * @access public
   */
  public $b;

  /**
   * 
   * @param string $businessUnit
   * @param string $ibmDocId
   * @param string $taskId
   * @param string $requestorId
   * @param string $transType
   * @param base64Binary $b
   * @access public
   */
  public function __construct($businessUnit, $ibmDocId, $taskId, $requestorId, $transType, $b)
  {
    $this->businessUnit = $businessUnit;
    $this->ibmDocId = $ibmDocId;
    $this->taskId = $taskId;
    $this->requestorId = $requestorId;
    $this->transType = $transType;
    $this->b = $b;
  }

}
