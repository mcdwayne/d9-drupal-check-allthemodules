<?php

namespace Drupal\comment_approver;

use GuzzleHttp\Client;
use Drupal\Core\Logger\LoggerChannelFactory;
use GuzzleHttp\Exception\RequestException;

/**
 * Class SentimentApi.
 *
 * Integrates with http://text-processing.com/docs/sentiment.html api.
 */
class SentimentApi implements SentimentApiInterface {

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Drupal\Core\Logger\LoggerChannelFactory definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * The api url.
   *
   * @var string
   */
  protected $url = "http://text-processing.com/api/sentiment/";

  /**
   * The language of text for analysis.
   *
   * Currently english,french and dutch are supported.
   *
   * @var string
   */
  protected $language = "english";

  /**
   * Stores the result of analysis.
   *
   * @var int
   */
  protected $result = SentimentApiInterface::NEUTRAL;

  /**
   * Stores all the probabilities.
   *
   * @var array
   */
  protected $probability;

  /**
   * Constructs a new SentimentApi object.
   */
  public function __construct(Client $http_client, LoggerChannelFactory $logger_factory) {
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function test(string $text, string $language = 'english') {
    $result = $this->result;
    $url = $this->url;
    $this->language = $language;
    $text = strip_tags($text);
    try {
      $client = $this->httpClient;
      $request = $client->post($url, [
        'form_params' => [
          'text' => $text,
          'language' => $language,
        ],
      ]);
      $response = json_decode($request->getBody());
      switch ($response->label) {
        case 'pos': $result = SentimentApiInterface::POSITIVE;
          break;

        case 'neg': $result = SentimentApiInterface::NEGATIVE;
          break;

        default: $result = SentimentApiInterface::NEUTRAL;
      }
      $this->probability = $response->probability;
    }
    catch (RequestException $ex) {
      $message = $ex->getMessage();
      $this->loggerFactory->get('comment_approver')->error($message);
    }
    $this->result = $result;
    return $result;
  }

  /**
   * Returns the result of the analyis.
   */
  public function getResult() {
    return $this->result;
  }

  /**
   * Returns the probability object of the analysis.
   */
  public function getProbability() {
    return $this->probability;
  }

}
