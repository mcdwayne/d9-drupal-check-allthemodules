<?php

namespace Drupal\elastic_email\Service;

use ElasticEmailClient\Account;
use ElasticEmailClient\ApiClient;
use ElasticEmailClient\Channel;
use ElasticEmailClient\Email;
use ElasticEmailClient\Log;

class ElasticEmailManager {

  public function __construct() {
    $apiKey = \Drupal::config('elastic_email.settings')->get('api_key');
    ApiClient::SetApiKey($apiKey);
  }

  /**
   * @return \ElasticEmailClient\Email
   */
  public function getEmail() {
    return new Email();
  }

  /**
   * @return \ElasticEmailClient\Account
   */
  public function getAccount() {
    return new Account();
  }

  /**
   * @return \ElasticEmailClient\Channel
   */
  public function getChannel() {
    return new Channel();
  }

  /**
   * @return \ElasticEmailClient\Log
   */
  public function getLog() {
    return new Log();
  }

}
