<?php

namespace Drupal\tmgmt_globaldoc\Service;

class echoCustom
{

  /**
   * 
   * @var string $requestMessage
   * @access public
   */
  public $requestMessage;

  /**
   * 
   * @param string $requestMessage
   * @access public
   */
  public function __construct($requestMessage)
  {
    $this->requestMessage = $requestMessage;
  }

}
