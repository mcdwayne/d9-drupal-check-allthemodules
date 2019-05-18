<?php

namespace Drupal\bisnode;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\ClientException;

/**
 * Class BisnodeService.
 */
class BisnodeService implements BisnodeServiceInterface {

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The bisnode url.
   */
  protected $url;


  /**
   * A base64 encode of credentials.
   */
  protected $credentials;

  /**
   * Constructs a new BisnodeService object.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;

    $config = \Drupal::config('bisnode.bisnodeconfig');
    $this->url = $config->get('bisnode_url');
    $this->credentials = base64_encode($config->get('bisnode_username') . ':' . $config->get('bisnode_password'));
  }


  /**
   * Search in Directory service.
   *
   * @param string $search
   *
   * @return
   */
  public function getDirectory($search) {
    $uri = $this->url . '/search/norway/directory';

    $response = $this->post($uri, $search);
    $json = (string) $response->getBody();
    $result = json_decode($json);

    if (isset($result->Result)) {
      return $this->buildValues($result->Result);
    }

    return [];
  }

  /**
   * Build values that will be used by webform.
   */
  protected function buildValues(array $results) {
    if (empty($results)) {
      return [];
    }

    $fields = self::fieldsMapping();

    foreach ($results as $result) {
      $this->buildFullName($result);
      $this->buildFinalAddress($result, $include_city = FALSE);
      $this->buildDateBirth($result);
    }

    return $results;
  }


  /**
   * Build the date of birth.
   */
  protected function buildDateBirth($result) {
    if ($result->born) {
      $result->date_birth = date('Y-m-d', strtotime($result->born));
    }
    else {
      $result->date_birth = '';
    }

  }


  /**
   * Build the full name.
   */
  protected function buildFullName($result) {
    // Complete fullname.
    $fullname = [];
    if ($result->firstname) {
      $fullname[] = $result->firstname;
    }
    if ($result->middlename) {
      $fullname[] = $result->middlename;
    }
    if ($result->lastname) {
      $fullname[] = $result->lastname;
    }

    $result->fullname = implode(' ', $fullname);
  }

  /**
   * Build the final address.
   *
   * Get the last address looking for "firstaquired" and "lastaquired" keys.
   */
  protected function buildFinalAddress($result, $include_city = TRUE) {

    $full_adress = [];

    if ($result->streetname) {
      $full_address[] = $result->streetname;
    }
    if ($result->houseno) {
      $full_address[] = $result->houseno;
    }
    if ($result->entrance) {
      $full_address[] = $result->entrance;
    }
    $result->final_address = implode(' ', $full_address);

    if ($include_city && $result->city) {
      $result->final_address .= ', ' . $result->city;
    }
  }


  /**
   * Request a post to the webservices.
   *
   * @param string $uri
   *
   * @param string $search
   *
   * @param array $options
   */
  protected function post($uri, $search, array $options = []) {
    $options += [
      'SearchMode' => 0,
      'OnlyFoundWords' => FALSE,
      'ListingType' => 0,
      'ResultLimitSearch' => 10,
    ];

    try {
      return $this->httpClient->post($uri, [
        'headers' => [
          'Authorization' => ['Basic ' . $this->credentials]
        ],
        RequestOptions::JSON => [
          'Form' => [
            'Type' => 'Freetext',
            'Searchstring' => $search,
          ],
          'Options' => $options,
        ],
      ]);
    }
    catch (ClientException $e) {
      throw new \Exception($e->getMessage());
    }
  }


  /**
   * Get the fields to be mapping.
   */
  static public function fieldsMapping() {

    return [
      'fullname' => t('Full name'),
      'firstname' => t('First name'),
      'lastname' => t('Last name'),
      'final_address' => t('Address'),
      'date_birth' => t('Date of birth'),
      'zipcode' => t('Zip code'),
      'city' => t('City'),
    ];
  }
}
