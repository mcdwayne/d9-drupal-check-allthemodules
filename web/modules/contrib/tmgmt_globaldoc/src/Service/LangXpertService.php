<?php

namespace Drupal\tmgmt_globaldoc\Service;

/**
 *
 */
class LangXpertService extends \SoapClient {

  /**
   * The classmap.
   *
   * @var array
   */
  private static $classmap = [
    'LangXpertServiceObject'=> 'Drupal\tmgmt_globaldoc\Service\LangXpertServiceObject',
    'GlobalDocStatesResponse'=> 'Drupal\tmgmt_globaldoc\Service\GlobalDocStatesResponse',
    'setTaskState'=> 'Drupal\tmgmt_globaldoc\Service\setTaskState',
    'setTaskStateResponse'=> 'Drupal\tmgmt_globaldoc\Service\setTaskStateResponse',
    'getTaskStates'=> 'Drupal\tmgmt_globaldoc\Service\getTaskStates',
    'getTaskStatesResponse'=> 'Drupal\tmgmt_globaldoc\Service\getTaskStatesResponse',
    'getPDFTask'=> 'Drupal\tmgmt_globaldoc\Service\getPDFTask',
    'getPDFTaskResponse'=> 'Drupal\tmgmt_globaldoc\Service\getPDFTaskResponse',
    'getSourceTask'=> 'Drupal\tmgmt_globaldoc\Service\getSourceTask',
    'getSourceTaskResponse'=> 'Drupal\tmgmt_globaldoc\Service\getSourceTaskResponse',
    'echo'=> 'Drupal\tmgmt_globaldoc\Service\echoCustom',
    'echoResponse'=> 'Drupal\tmgmt_globaldoc\Service\echoResponse',
    'getTaskState'=> 'Drupal\tmgmt_globaldoc\Service\getTaskState',
    'getTaskStateResponse'=> 'Drupal\tmgmt_globaldoc\Service\getTaskStateResponse',
    'submitPDFTask'=> 'Drupal\tmgmt_globaldoc\Service\submitPDFTask',
    'submitPDFTaskResponse'=> 'Drupal\tmgmt_globaldoc\Service\submitPDFTaskResponse',
    'submitSourceTask'=> 'Drupal\tmgmt_globaldoc\Service\submitSourceTask',
    'submitSourceTaskResponse'=> 'Drupal\tmgmt_globaldoc\Service\submitSourceTaskResponse',
  ];

  /**
   * Constructs a LangXpertService instance.
   *
   * @param string $security_token
   *   The security token.
   * @param string $wsdl
   *   The WSDL file to use.
   *
   */
  public function __construct($security_token, $wsdl) {
    parent::__construct($wsdl, ['exceptions' => TRUE]);

    $header = new \SoapHeader('http://service.langxpert.globaldoc.com', 'securityToken', $security_token);
    //set the Headers of Soap Client.
    $this->__setSoapHeaders($header);
  }

  /**
   *
   */
  public function __doRequest($request, $location, $action, $version, $one_way = 0) {
    $response = parent::__doRequest($request, $location, $action, $version, $one_way);

    // We need to filter out the MTOM wrapper text around the XML, see
    // https://stackoverflow.com/questions/18330022/sending-and-receiving-files-with-php-soap-and-mtom.

    $start = strpos($response, '<?xml');
    $end = strrpos($response, '>');
    $response_string = substr($response, $start, $end - $start + 1);
    return $response_string;
  }

  /**
   *
   * @param getPDFTask $parameters
   *
   * @access public
   */
  public function getPDFTask(getPDFTask $parameters) {
    return $this->__soapCall('getPDFTask', [$parameters]);
  }

  /**
   *
   * @param getTaskStates $parameters
   *
   * @access public
   */
  public function getTaskStates(getTaskStates $parameters) {
    return $this->__soapCall('getTaskStates', [$parameters]);
  }

  /**
   *
   * @param getTaskState $parameters
   *
   * @access public
   */
  public function getTaskState(getTaskState $parameters) {
    return $this->__soapCall('getTaskState', [$parameters]);
  }

  /**
   *
   * @param getSourceTask $parameters
   *
   * @access public
   */
  public function getSourceTask(getSourceTask $parameters) {
    return $this->__soapCall('getSourceTask', [$parameters]);
  }

  /**
   *
   * @param setTaskState $parameters
   *
   * @access public
   */
  public function setTaskState(setTaskState $parameters) {
    return $this->__soapCall('setTaskState', [$parameters]);
  }

  /**
   *
   * @param echoCustom $parameters
   *
   * @return \Drupal\tmgmt_globaldoc\Service\echoResponse
   */
  public function callEcho($parameters) {
    try {
      return $this->__soapCall('echo', [$parameters]);
    }
    catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   *
   * @param submitSourceTask $parameters
   *
   * @access public
   */
  public function submitSourceTask(submitSourceTask $parameters) {
    return $this->__soapCall('submitSourceTask', [$parameters]);
  }

  /**
   *
   * @param submitPDFTask $parameters
   *
   * @access public
   */
  public function submitPDFTask(submitPDFTask $parameters) {
    return $this->__soapCall('submitPDFTask', [$parameters]);
  }

}
