<?php

namespace Drupal\webform_epetition;

/**
 * Class WebformEpetitionMsp.
 */
class WebformEpetitionSearch implements WebformEpetitionSearchInterface {

  protected $queryParam;

  protected $dataType;

  protected $client;

  protected $people;

  protected $results;


  /**
   * WebformEpetitionSearch constructor.
   *
   * @param \Drupal\webform_epetition\WebformEpetitionClientInterface $client
   */
  public function __construct(WebformEpetitionClientInterface $client) {
    $this->client = $client;
  }

  /**
   * @return mixed
   */
  public function getResults() {
    return $this->searchApi();
  }

  /**
   * @param mixed $queryParam
   */
  public function setQueryParam($queryParam): void {
    $this->queryParam = $queryParam;
  }

  /**
   * @param $dataType
   */
  public function setDataType($dataType): void {
    $this->dataType = $dataType;
  }

  /**
   * @return mixed
   */
  private function searchApi() {

    $this->people = $this->client->sendRequest(
      $this->dataType,
      $this->queryParam
    );

    return $this->people;
  }

}
