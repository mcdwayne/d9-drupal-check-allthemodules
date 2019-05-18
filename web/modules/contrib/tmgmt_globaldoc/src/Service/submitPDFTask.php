<?php

namespace Drupal\tmgmt_globaldoc\Service;

class submitPDFTask
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
   * @param string $requestorId
   * @param string $taskId
   * @param string $transType
   * @param base64Binary $b
   * @access public
   */
  public function __construct($businessUnit, $ibmDocId, $requestorId, $taskId, $transType, $b)
  {
    $this->businessUnit = $businessUnit;
    $this->ibmDocId = $ibmDocId;
    $this->requestorId = $requestorId;
    $this->taskId = $taskId;
    $this->transType = $transType;
    $this->b = $b;
  }

}
