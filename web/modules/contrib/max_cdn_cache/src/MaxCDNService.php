<?php

namespace Drupal\max_cdn_cache;

/**
 * MaxCDN Service.
 */
class MaxCDNService {

  protected $companyAlias;
  protected $consumerKey;
  protected $consumerSecret;
  protected $client;

  /**
   * Returns if all the 3 credentials available.
   */
  public function getCredentials() {
    $companyAlias = $this->getConfig('max_cdn_cache_alias');
    $consumerKey = $this->getConfig('max_cdn_cache_consumer_key');
    $consumerSecret = $this->getConfig('max_cdn_cache_consumer_secret');

    if (isset($companyAlias) && isset($consumerKey) && isset($consumerSecret)) {
      // Credentials present.
      $this->company_alias = $companyAlias;
      $this->consumer_key = $consumerKey;
      $this->consumer_secret = $consumerSecret;
      return array(
        'company_alias' => $companyAlias,
        'consumer_key' => $consumerKey,
        'consumer_secret' => $consumerSecret,
      );
    }
    else {
      drupal_set_message(t('MAxCDN Cache: Credentials not present.'), 'warning');
      return FALSE;
    }
  }

  /**
   * Constructor for MaxCDN service.
   */
  public function __construct() {
    try {
      if (!$this->getCredentials()) {
        return FALSE;
      }
      // Set client.
      $this->client = new \MaxCDN(
        $this->getConfig('max_cdn_cache_alias'),
        $this->getConfig('max_cdn_cache_consumer_key'),
        $this->getConfig('max_cdn_cache_consumer_secret')
      );
    }
    catch (\Exception $e) {
      drupal_set_message(t('MaxCDN Error : @e', ['@e' => $e->getMessage()]), 'error');
    }
  }

  /**
   * Get Zone List from API.
   */
  public function getZoneList() {
    $collect_zoneid = array('' => '-Select Zone-');
    $api_call = $this->client->get('/zones/pull.json?nopaginate=1');
    $zone_list = json_decode($api_call);
    if (array_key_exists("code", $zone_list)) {
      if ($zone_list->code == 200 || $zone_list->code == 201) {
        foreach ($zone_list->data->pullzones as $value) {
          $collect_zoneid[$value->id] = $value->name;
        }
      }
    }
    return $collect_zoneid;
  }

  /**
   * Return return API values.
   */
  protected function getConfig($config) {
    return \Drupal::config('max_cdn_cache.settings')->get($config);
  }

  /**
   * Delete selected Zone.
   */
  public function deleteZone($zoneid) {
    try {
      $delete_api = $this->client->delete("/zones/pull.json/$zoneid/cache");
      $return_value = json_decode($delete_api);
      if (array_key_exists("code", $return_value)) {
        if ($return_value->code == 200 || $return_value->code == 201) {
          $zone_name_array = $this->getZoneList();
          $zone_name = $zone_name_array[$zoneid];
          drupal_set_message(t("Cache cleared for Zone: %zonename", array('%zonename' => $zone_name)));
        }
      }
    }
    catch (\Exception $e) {
      drupal_set_message(t('MaxCDN Error : @e', ['@e' => $e->getMessage()]), 'error');
    }
  }

}
