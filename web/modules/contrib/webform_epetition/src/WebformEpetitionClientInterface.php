<?php

namespace Drupal\webform_epetition;

/**
 * Interface WebformEpetitionClientInterface.
 */
interface WebformEpetitionClientInterface {

  /**
   * @param $red_type
   * @param array $url_params
   *
   * @return mixed
   */
  public function sendRequest($red_type, array $url_params);

}
