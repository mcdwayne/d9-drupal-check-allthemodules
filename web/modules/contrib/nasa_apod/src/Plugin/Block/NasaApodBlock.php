<?php

namespace Drupal\nasa_apod\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

/**
 * Provides a block that displays NASA's Astronomy Picture of the Day (APOD).
 *
 * @Block(
 *   id = "nasa_apod_block",
 *   admin_label = @Translation("NASA APOD Block"),
 *   category = @Translation("NASA API")
 * )
 */
class NasaApodBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $data = $this->getApod();

    return [
      '#theme' => 'nasa_apod',
      '#apod_title' => $this->t($data['title']),
      '#date' => $data['date'],
      '#img' => $data['img'],
      '#explanation' => $this->t($data['explanation']),
      '#copyright' => $data['copyright'],
      '#error' => $data['error'],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Retrieve NASA APOD from API.
   */
  private function getApod(string $date = '') {
    $data = [
      'apod_title' => '',
      'date' => '',
      'img' => '',
      'explanation' => '',
      'copyright' => '',
      'error' => FALSE,
    ];
    $config = \Drupal::config('nasa_apod.settings');
    $query_params = [
      'date' => empty($date) ? date("Y-m-d") : $date,
      'hd' => empty($config->get('hi_res')) ? FALSE : TRUE,
      'api_key' => $config->get('key'),
    ];
    $url = $config->get('url') . '?' . http_build_query($query_params);

    try {
      $response = \Drupal::httpClient()->get($url);
      $response = (string) $response->getBody();
      $response = json_decode($response);
      $data['title'] = $response->title;
      $data['date'] = date("F j, Y", strtotime($response->date));
      $data['img'] = $config->get('hi_res') ? $response->hdurl : $response->url;
      $data['explanation'] = $response->explanation;
      $data['copyright'] = $response->copyright ? $response->copyright : '';
    }
    catch (RequestException $e) {
      $response = (string) $e->getResponse()->getBody();
      $response = json_decode($response);
      if ($response->error->code === 'API_KEY_MISSING') {
        $error = 'You need to set your API key in the NASA APOD module configuration.';
      } else {
        $error = $response->error->message;
      }
      $data['title'] = 'Oops... An error occurred!';
      $data['explanation'] = $error;
      $data['error'] = TRUE;
    }
    return $data;
  }

}
