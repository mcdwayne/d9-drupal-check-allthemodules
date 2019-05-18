<?php

namespace Drupal\qwantsearch\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class QwantSearch.
 */
class QwantSearch implements QwantSearchInterface {
  use StringTranslationTrait;

  /**
   * Qwant search endpoint.
   *
   * @var string
   */
  private static $endpoint = 'https://api.qwant.com/partners/!partner_id/search';

  /**
   * The factory for configuration objects.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Qwant http token.
   *
   * @var string
   */
  public $httpToken;

  /**
   * Qwant partner id.
   *
   * @var string
   */
  public $partnerId;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    $this->httpToken = $this->configFactory->get('qwantsearch.settings')->get('qwantsearch_http_token');
    $this->partnerId = $this->configFactory->get('qwantsearch.settings')->get('qwantsearch_partner_id');
  }

  /**
   * {@inheritdoc}
   */
  public function makeQuery(array $params = []) {
    $params += [
      'q' => ' ',
      'offset' => 0,
      'count' => $this->configFactory->get('qwantsearch.settings')->get('qwantsearch_nb_items_displayed'),
      'f' => 'order:relevance',
    ];

    $endpoint = str_replace('!partner_id', $this->partnerId, self::$endpoint);
    $endpoint .= '?' . http_build_query($params);

    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_URL => $endpoint,
      CURLOPT_HTTPHEADER => [
        'token: ' . $this->httpToken,
      ],
    ]);
    $result = curl_exec($curl);

    return json_decode($result);
  }

  /**
   * {@inheritdoc}
   */
  public function isSuccess($response) {
    return $response->status == 'success';
  }

}
