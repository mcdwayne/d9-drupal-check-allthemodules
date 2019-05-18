<?php

/**
 * @file
 * Contains Drupal\hubspot_forms\HubspotFormsCore.
 */

namespace Drupal\hubspot_forms;

use Drupal\hubspot_forms\Plugins;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class HubspotFormsCore.
 *
 * @package Drupal\hubspot_forms
 */
class HubspotFormsCore {

  use StringTranslationTrait;

  /**
   * Get form ids.
   */
  public function getFormIds() {
    $cid = 'hubspot_forms';
    $forms = NULL;
    if ($cache = \Drupal::cache()->get($cid)) {
      $forms = $cache->data;
    }
    else {
      $forms = $this->fetchHubspotForms();
      \Drupal::cache()->set($cid, $forms);
    }
    $form_ids = [
      '' => $this->t('Choose a Hubspot form'),
    ];
    if (!empty($forms)) {
      foreach ($forms as $item) {
        $form_ids[$item->portalId . '::' . $item->guid] = $item->name;
      }
    }
    return $form_ids;
  }

  /**
   * Make an API call to Hubspot Forms API
   * and get a list of all available forms.
   */
  public function fetchHubspotForms() {
    $config = \Drupal::config('hubspot_forms.settings');
    $api_key = $config->get('hubspot_api_key');
    try {
      // [Get all forms from a portal](http://developers.hubspot.com/docs/methods/forms/v2/get_forms)
      $uri = 'https://api.hubapi.com/forms/v2/forms?hapikey=' . $api_key;
      $client = \Drupal::httpClient(['base_url' => $uri]);
      $request = $client->request('GET', $uri, ['timeout' => 5, 'headers' => ['Accept' => 'application/json']]);
      if ($request->getStatusCode() == 200) {
        $response = json_decode($request->getBody());
        if (empty($response)) {
          return [];
        }
        else {
          return $response;
        }
      }
      else {
        return [];
      }
    }
    catch (\GuzzleHttp\Exception\ClientException $e) {
      $message = $e->getMessage() . '. Make sure you provided correct Hubspot API Key on the configuration page.';
      \Drupal::logger('hubspot_forms')->notice($message);
      return [];
    }
    catch (\GuzzleHttp\Exception\ConnectException $e) {
      $message = $e->getMessage();
      \Drupal::logger('hubspot_forms')->notice($message);
      return [];
    }
  }

  /**
   * Check Hubspot connection.
   */
  public function isConnected() {
    $forms = $this->fetchHubspotForms();
    return count($forms);
  }

}
