<?php

namespace Drupal\drupal_inquicker\Source;

/**
 * An Inquicker source which connects to the Inquicker API.
 */
class LiveSource extends Source {

  /**
   * Fetch the API key defined in the config.
   *
   * @return string
   *   The API key.
   *
   * @throws \Exception
   */
  public function apiKey() : string {
    return $this->config('key');
  }

  /**
   * {@inheritdoc}
   */
  public function live() : bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function modifyQuery(array $query) : array {
    $return = $query;
    $return['api_key'] = $this->apiKey();
    return parent::modifyQuery($return);
  }

  /**
   * {@inheritdoc}
   */
  public function response($uri, $options = []) {
    return $this->httpGet($uri, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function url() : string {
    return $this->config('url');
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $this->apiKey();
  }

}
