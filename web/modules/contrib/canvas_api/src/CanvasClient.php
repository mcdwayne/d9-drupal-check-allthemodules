<?php

namespace Drupal\canvas_api;

use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Config\ConfigFactory;

class CanvasClient{
  
  public $client;

  /**
   * Construct the HTTP client for making Canvas API calls.
   *
   * @param ClientFactory $client
   * @param ConfigFactory $config
   */
  function __construct(ClientFactory $client, ConfigFactory $config){
    $canvasSettings = $config->get('canvas_api.settings');
    $env = $canvasSettings->get('environment');
    $inst = $canvasSettings->get('institution');

    switch($env){
      case 'production':
        $url = $inst;
      break;
      case 'test':
        $url = $inst . '.test';
        break;
      case 'beta':
        $url = $inst . '.beta';
      break;
      default:
        $msg = 'You need to set your Canvas environment!';
        drupal_set_message($msg,'error');
        return;
    }
    $url .= '.instructure.com';

    $options = array(
      'headers'=> array(
        'Authorization' => 'Bearer ' . $canvasSettings->get('token'),
       ),    
      'base_uri' => 'https://' . $url,
      'timeout' => 60,
    );
    $this->client = $client->fromOptions($options);
  }
  

}