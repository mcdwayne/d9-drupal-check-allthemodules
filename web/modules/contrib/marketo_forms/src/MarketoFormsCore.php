<?php

/**
 * @file
 * Contains Drupal\marketo_forms\MarketoFormsCore.
 */

namespace Drupal\marketo_forms;

use Drupal\marketo_forms\Plugins;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class MarketoFormsCore.
 *
 * @package Drupal\marketo_forms
 */
class MarketoFormsCore {

  use StringTranslationTrait;

  /**
   * Get form ids.
   */
  public function getFormIds() {
    $cid = 'marketo_forms';
    $forms = NULL;
    if ($cache = \Drupal::cache()->get($cid)) {
      $forms = $cache->data;
    }
    else {
      $forms = $this->fetchMarketoForms();
      \Drupal::cache()->set($cid, $forms);
    }
    $form_ids = [
      '' => $this->t('Choose a Marketo form'),
    ];
    if (!empty($forms)) {
      foreach ($forms as $item) {
        $form_ids[$item->portalId . '::' . $item->guid] = $item->name;
      }
    }
    return $form_ids;
  }

  /**
   * Make an API call to Marketo Forms API
   * and get a list of all available forms.
   */
  public function fetchMarketoForms() {
    $config = \Drupal::config('marketo_forms.settings');
    $api_key = $config->get('marketo_api_key');
    try {
      // [Get all forms from a portal](http://developers.marketo.com/docs/methods/forms/v2/get_forms)
      $uri = 'https://api.hubapi.com/forms/v2/forms?hapikey=' . $api_key;
      $request = \Drupal::httpClient()->get($uri, ['headers' => ['Accept' => 'application/json']]);
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
      $message = $e->getMessage() . '. Make sure you provided correct Marketo API Key on the configuration page.';
      \Drupal::logger('marketo_forms')->notice($message);
      return [];
    }
  }

  /**
   * Check Marketo connection.
   */
  public function isConnected() {
    $forms = $this->fetchMarketoForms();
    return count($forms);
  }

}
