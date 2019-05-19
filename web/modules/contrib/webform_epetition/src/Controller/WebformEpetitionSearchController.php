<?php

namespace Drupal\webform_epetition\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\webform_epetition\WebformEpetitionFormatInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\webform_epetition\WebformEpetitionSearchInterface;

/**
 * Class WebformEpetitionSearchController.
 */
class WebformEpetitionSearchController extends ControllerBase {

  protected $webformEpSearch;

  protected $webformEpFormat;

  protected $response;

  /**
   * Constructs a new WebformEpetitionSearchController object.
   *
   * @param \Drupal\webform_epetition\WebformEpetitionSearchInterface $webform_ep_search
   * @param \Drupal\webform_epetition\WebformEpetitionFormatInterface $webform_ep_format
   */
  public function __construct(WebformEpetitionSearchInterface $webform_ep_search, WebformEpetitionFormatInterface $webform_ep_format) {
    $this->webformEpSearch = $webform_ep_search;
    $this->webformEpFormat = $webform_ep_format;
    $this->response = new Response();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform_epetition.search'),
      $container->get('webform_epetition.format')
    );
  }



  /**
   * @param $postcode
   * @param $datatype
   * @param $format
   *
   * @return string
   */
  public function searchForRepresentatives($postcode, $datatype, $format) {

    if(empty($postcode) || empty($datatype) || empty($format)) {
      return "Postcode, datatype and format need to be set!";
    }
    else {
      $result = '';
      $response = $this->postcodeQuery($postcode, $datatype);
      $this->webformEpFormat->setResponse($response);
      $this->webformEpFormat->setDataType($datatype);

      switch ($format) {
        case 'emails':
          $result = $this->webformEpFormat->getEmails();
          break;
        case 'details':
          $result = $this->webformEpFormat->getDetails();
          break;
        case 'names':
          $result = $this->webformEpFormat->getNames();
          break;
      }
      return $this->response->setContent($result);
    }
  }

  /**
   * @param $postcode
   * @param $datatype
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  private function postcodeQuery($postcode, $datatype) {
    $query = ['postcode' => $postcode];
    $this->webformEpSearch->setQueryParam($query);
    $this->webformEpSearch->setDataType($datatype);
    return $this->webformEpSearch->getResults();

  }

}
