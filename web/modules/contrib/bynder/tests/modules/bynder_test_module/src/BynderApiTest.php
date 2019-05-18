<?php

namespace Drupal\bynder_test_module;

use Drupal\bynder\BynderApi;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

/**
 * Bynder API test service.
 *
 * @package Drupal\bynder
 */
class BynderApiTest extends BynderApi {

  const BYNDER_INTEGRATION_ID = 'a7129512-c6e3-47a3-be40-9a66503e82ed';

  public function getIntegrationId() {
    return self::BYNDER_INTEGRATION_ID;
  }

  /**
   * Returns the defined value.
   */
  public function getBrands() {
    if ($brands = $this->state->get('bynder.bynder_test_brands')) {
      return $brands;
    }

    throw new RequestException('Test', new Request('test', 'test'));
  }

  /**
   * Returns value set in state.
   *
   * @param string $media_uuid
   *   The media UUID.
   *
   * @return mixed
   *   Returns what is set in the state.
   */
  public function getMediaInfo($media_uuid) {
    return $this->state->get('bynder.bynder_test_media_info');
  }

  /**
   * Returns value set in state.
   *
   * @param array $query
   *   Search query.
   *
   * @return mixed
   *   Returns what is set in the state.
   *
   * @throws \Exception
   *   Connection fails.
   */
  public function getMediaList(array $query) {
    if (!($media_list = $this->state->get('bynder.bynder_test_media_list'))) {
      throw new \Exception();
    }

    // Filter on keyword.
    if (!empty($query['keyword'])) {
      foreach ($media_list['media'] as $key => $media) {
        if ($query['keyword'] != $media['keyword']) {
          unset($media_list['media'][$key]);
        }
      };
    }

    // Filter on meta-property options.
    $metaproperties = array_filter(
      $query,
      function ($key) { return strpos($key, 'property_') === 0; },
      ARRAY_FILTER_USE_KEY
    );
    if ($metaproperties) {
      foreach ($metaproperties as $metaproperty => $options) {
        $options = explode(',', $options);
        foreach ($media_list['media'] as $key => $media) {
          if (empty(array_intersect($options, $media[$metaproperty]))) {
            unset($media_list['media'][$key]);
          }
        };
      }
    }

    // Filter on tags.
    if (!empty($query['tags'])) {
      foreach ($media_list['media'] as $key => $media) {
        if (!in_array($query['tags'], $media['tags'])) {
          unset($media_list['media'][$key]);
        }
      };
    }

    return $media_list;
  }

  /**
   * Returns value set in state.
   */
  public function getMetaproperties() {
    if (!$this->state->get('bynder.bynder_test_metaproperties')) {
      throw new \Exception();
    }
    return $this->state->get('bynder.bynder_test_metaproperties');
  }

  /**
   * Returns value set in state.
   */
  public function hasAccessToken() {
    if (!$this->state->get('bynder.bynder_test_access_token')) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Returns value set in state.
   */
  public function getTags($query = []) {
    if (!$this->state->get('bynder.bynder_test_tags')) {
      throw new \Exception();
    }
    return $this->state->get('bynder.bynder_test_tags');
  }

  /**
   * Returns value set in state.
   */
  public function getDerivatives() {
    if (!is_array($this->state->get('bynder.bynder_test_derivatives'))) {
      throw new \Exception();
    }
    return $this->state->get('bynder.bynder_test_derivatives');
  }

  /**
   * Returns value set in state.
   *
   * @param string $media_uuid
   *   The media UUID.
   *
   * @return array
   *   Returns array of expected values.
   */
  public function uploadFileAsync($media_uuid) {
    return [
      'success' => $this->state->get('bynder.bynder_test_upload_success'),
      'mediaid' => $this->state->get('bynder.bynder_test_upload_mediaid'),
    ];
  }

  /**
   * Simulate deleting media and returns void.
   *
   * @param string $media_uuid
   *   The media UUID.
   */
  public function deleteMedia($media_uuid) {}

  /**
   * Simulate upload permissions check.
   */
  public function hasUploadPermissions() {
    return 'MEDIAUPLOAD';
  }

  /**
   * Simulate cache update.
   */
  public function updateCachedData() {}

  /**
   * Sets values in state.
   *
   * @param string $integration_id
   *   The Drupal 8 integration id.
   * @param string $asset_id
   *   The Bynder media id.
   * @param string $timestamp
   *   Current timestamp.
   * @param string $location
   *   Url location for entity that references this asset.
   * @param string $additional
   *   Additional media info.
   */
  public function addAssetUsage($asset_id, $usage_url, $creation_date, $additional_info = NULL) {
    $values = [
      'integration_id' => $this->getIntegrationId(),
      'asset_id' => $asset_id,
      'timestamp' => $creation_date,
      'location' => $usage_url,
      'additional' => $additional_info
    ];
    $this->state->set('bynder.bynder_add_usage', $values);
  }

  /**
   * Sets values in state.
   *
   * @param string $integration_id
   *   The Drupal 8 integration id.
   * @param string $asset_id
   *   The Bynder media id.
   * @param string $location
   *   Url location for entity that references this asset.
   */
  public function removeAssetUsage($asset_id, $usage_url = NULL) {
    $values = [
      'integration_id' => $this->getIntegrationId(),
      'asset_id' => $asset_id,
      'location' => $usage_url,
    ];
    $this->state->set('bynder.bynder_delete_usage', $values);
  }

}
