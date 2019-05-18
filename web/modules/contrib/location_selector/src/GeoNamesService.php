<?php

namespace Drupal\location_selector;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Class GeoNamesService.
 */
class GeoNamesService {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new GeoNamesService object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, AccountProxyInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
  }

  /**
   * Returns the children or infos from the requestet id's.
   *
   * @param array $ids
   *   Must be the format:
   *   'children' => id
   *   or/and
   *   'parent' => id.
   *
   * @return array|null
   *   The array or null.
   */
  public function getGeoNamesAndIds(array $ids) {
    // Some basic data.
    $infos = NULL;
    $empty_value = t('- Please select -');
    $empty_option = ['val' => 'All', 'text' => $empty_value->render()];

    // Loop trough array and save the API results.
    foreach ($ids as $key1 => $id) {
      // If more than one value, the parent needs to be included.
      if (isset($id['parent'])) {
        if (!empty($data = $this->checkAndSetCaching($id['parent'], 'getInfos', $empty_option))) {
          $result_array[$key1]['parent'] = $data;
        }
      }
      if (isset($id['children'])) {
        if (!empty($data = $this->checkAndSetCaching($id['children'], 'getChildren', $empty_option))) {
          $result_array[$key1]['children'] = $data;
        }
      }
    }
    if (!empty($result_array)) {
      $infos = $result_array;
    }
    return $infos;
  }

  /**
   * Cache handling.
   *
   * @param int|string $api_id
   *   The ids for the select lists.
   * @param string $method
   *   The api method.
   * @param array $empty_option
   *   The empty select option value and text.
   *
   * @return array|null
   *   return select array.
   */
  protected function checkAndSetCaching($api_id, string $method, array $empty_option) {
    $data = NULL;
    $language_id = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $cid = 'location_selector:' . $method . ':' . $api_id . ':' . $language_id;
    if ($cache = \Drupal::cache()->get($cid)) {
      $data = $cache->data;
    }
    else {
      $data = $this->createSelectArray($api_id, $method, $empty_option);
      if (!empty($data)) {
        // @see createSelectArray()
        if ($data === 'no-children') {
          $data = NULL;
        }
        \Drupal::cache()->set($cid, $data, CacheBackendInterface::CACHE_PERMANENT);
      }
    }
    return $data;
  }

  /**
   * Create the select array for select lists.
   *
   * @param int|string $api_id
   *   The ids for the select lists.
   * @param string $method
   *   The api method.
   * @param array $empty_option
   *   The empty select option value and text.
   *
   * @return array|null|string
   *   return select array.
   */
  protected function createSelectArray($api_id, string $method, array $empty_option) {
    $array = NULL;
    switch ($method) {
      case 'getChildren':
        $children = $this->getChildren($api_id);
        if (!empty($children['geonames'])) {
          $results = $children['geonames'];
        }
        elseif (isset($children['geonames'])) {
          // Because ID's without children should be cached too.
          // And the return is not specific empty.
          // @see checkAndSetCaching()
          $array = 'no-children';
        }
        break;

      case 'getInfos':
        $results[] = $this->getInfos($api_id);
        break;
    }
    if (!empty($results)) {
      foreach ($results as $key => $result) {
        $array[$key] = [
          'val' => $result['geonameId'],
          'text' => $result['name'],
        ];
      }
    }
    if (!empty($array) && is_array($array)) {
      array_unshift($array, $empty_option);
    }
    return $array;
  }

  /**
   * Get the infos.
   *
   * @param string|int $genames_id
   *   The specific GeoNames ID.
   *
   * @return array|null
   *   return the specific data.
   */
  public function getInfos($genames_id) {
    $data = NULL;
    $language_id = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $url = 'http://api.geonames.org/getJSON?&geonameId=' . $genames_id . '&lang=' . $language_id;
    if (!empty($results = $this->geoNamesApiCall('GET', $url))) {
      $data = $results;
    }
    return $data;
  }

  /**
   * Get the children.
   *
   * @param string|int $parent_id
   *   The parent id.
   *
   * @return array|null
   *   return children.
   */
  public function getChildren($parent_id) {
    $data = NULL;
    $language_id = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $url = 'http://api.geonames.org/childrenJSON?&geonameId=' . $parent_id . '&lang=' . $language_id;
    if (!empty($results = $this->geoNamesApiCall('GET', $url))) {
      $data = $results;
    }
    return $data;
  }

  /**
   * Make the API call.
   *
   * @param string $method
   *   The method.
   * @param string $url
   *   The url.
   * @param array|bool $data
   *   The data.
   *
   * @see https://stackoverflow.com/questions/9802788/call-a-rest-api-in-php
   * @see http://www.geonames.org/export/ws-overview.html
   *
   * @return array|null
   *   return the result.
   */
  public function geoNamesApiCall($method, $url, $data = FALSE) {
    $result_call = NULL;

    $location_selector_configs = $this->configFactory->get('location_selector.settings');
    if (!empty($username = $location_selector_configs->get('geonames_username'))) {

      $auth_url = substr_replace($url, 'username=' . $username, (strpos($url, '?') + 1), 0);

      // Method: POST, PUT, GET etc.
      // Data: array("param" => "value") ==> index.php?param=value
      $curl = curl_init();
      switch ($method) {
        case "POST":
          curl_setopt($curl, CURLOPT_POST, 1);
          if ($data) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
          }
          break;

        case "PUT":
          curl_setopt($curl, CURLOPT_PUT, 1);
          break;

        default:
          if ($data) {
            $auth_url = sprintf("%s?%s", $auth_url, http_build_query($data));
          }
      }

      // Optional Authentication:
      // curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      // curl_setopt($curl, CURLOPT_USERPWD, "username:password");

      curl_setopt($curl, CURLOPT_URL, $auth_url);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

      $result = curl_exec($curl);

      curl_close($curl);

      $error = TRUE;
      if (!empty($result)) {
        $json_result = json_decode($result, TRUE);
        if (json_last_error() == JSON_ERROR_NONE) {
          if (!empty($json_result['totalResultsCount']) || !isset($json_result['status'])) {
            $error = FALSE;
          }
        }
      }
      if (!$error && isset($json_result)) {
        $result_call = $json_result;
      }
      else {
        \Drupal::logger('location_selector')->critical(
          'Error with the GeoNames API.
          <br>File: @file<br>Line: @line<br>Function: @function<br>Infos: <pre>@infos</pre>', [
            '@infos' => print_r($result, TRUE),
            '@file' => __FILE__,
            '@line' => __LINE__,
            '@function' => __FUNCTION__,
          ]
        );
      }

    }
    return $result_call;
  }

}
