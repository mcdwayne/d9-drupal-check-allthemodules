<?php

/**
 * @file
 * Contains \Drupal\sjisocialconnect\Controller\BitlyController.
 */

namespace Drupal\sjisocialconnect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Returns responses for Bitly API.
 */
class BitlyController extends ControllerBase {

  /**
   * The Application Username.
   *
   * @var string
   */
  public $username;
  
  /**
   * The Application App key.
   *
   * @var string
   */
  public $apikey;
  
  /**
   * The Bilty config.
   */
  public $sjisocialconnect_bitly;
  
  /**
   * Constructs a Publish Content Bilty object.
   */
  public function getConfig($default_values = array()) {
    if (empty($default_values)) {
      $this->sjisocialconnect_bitly = \Drupal::config('sjisocialconnect.bitly')->getRawData();
      if (!empty($this->sjisocialconnect_bitly)) {
        $bilty_conf = $this->sjisocialconnect_bitly['bitly'];
        foreach ($bilty_conf as $key => $value) {
          $this->$key = $value;
        }
      }
    }
    else {
      foreach ($default_values as $key => $value) {
        $this->$key = $value;
      }
    }
  }
  
  public function formElement($default_values = array()) {
    self::getConfig($default_values);
    $access = \Drupal::currentUser()->hasPermission('administer sji social connect');
    $form = array();
    
    // Bitly settings.
    $form['bitly'] = array(
      '#type' => 'details',
      '#title' => t('Bitly settings'),
      '#description' => t('To shorten the links visit <a href="@bitlyurl" target="_blank" />@bitlyurl</a> and get your Bilty API Key', array('@bitlyurl' => 'https://bitly.com/a/your_api_key')),
      '#access' => $access,
      '#open' => \Drupal::service('path.matcher')->matchPath(Url::fromRoute('<current>'), '/admin/config/services/publish-away/*'),
    );
    $form['bitly']['username'] = array(
      '#title' => t('Bitly username'),
      '#type' => 'textfield',
      '#default_value' => $this->username,
      '#required' => FALSE,
      '#access' => $access,
    );
    $form['bitly']['apikey'] = array(
      '#title' => t('Bitly API Key'),
      '#type' => 'textfield',
      '#default_value' => $this->apikey,
      '#required' => FALSE,
      '#access' => $access,
    );
    
    return $form;
  }
  
}
