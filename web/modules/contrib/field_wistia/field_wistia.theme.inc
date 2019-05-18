<?php

use Drupal\Core\Render\Markup;
Use Drupal\Core\Url;
Use Drupal\Component\Serialization\Json;
Use \Drupal\Component\Utility\UrlHelper;
Use \GuzzleHttp\Exception\RequestException;

/**
 * Prepares variables for the YouTube Video template.
 *
 * Default template: field-wistia-video.html.twig.
 */

function template_preprocess_field_wistia_video(&$variables)
{

  $variables['settings'] += [
    'field_wistia_embed_type' => \Drupal::config('field_wistia.settings')->get('field_wistia_embed_type'),
    'field_wistia_width' => \Drupal::config('field_wistia.settings')->get('field_wistia_width'),
    'field_wistia_height' => \Drupal::config('field_wistia.settings')->get('field_wistia_height'),
  ];

  // Build the query for embedded videos
  $query = [];

  $url = $variables['url'];
  $size = $variables['settings']['field_wistia_size'];
  $width = $variables['settings']['field_wistia_width'];
  $height = $variables['settings']['field_wistia_height'];
  $dimensions = field_wistia_get_dimensions($size, $width, $height);

  $query['height'] = $dimensions['height'];
  $query['width'] = $dimensions['width'];
  $query['embedType'] = $variables['settings']['field_wistia_embed_type'];

  $url = Url::fromUri($url, ['query' => $query]);
  // We don't need to URL encode path since Guzzle get method will do that for us
  $url = $url->toString();

  $params = ['url' => $url];

  // This sends the oembed request to wistia and gets a json array in return.
  // The html key in the array holds renderable markup.

  $embed = _field_wistia_request_embed($params);

  // Build the embed render array.
  $variables['embed'] = [
      '#markup' => Markup::create($embed['html']),
  ];

  //

}

/**
 * Request to embed code from the wistia get embed endpoint.
 *
 * Wistia returns a json array via oembed queury where the html key
 * is the renderable element.
 *
 * @param array $query
 *   Wistia oembed query array.
 *
 * @return array|bool
 *   Return array via Wistia oembed or false.
 */
function _field_wistia_request_embed(array $query)
{
  // Make HTTP request.
  $endpoint = FIELD_WISTIA_DEFAULT_OEMBED_URL;
  $client = \Drupal::httpClient();

  try {
    $response = $client->get($endpoint, ['query' => $query]);
    $data = Json::decode($response->getBody());
  }
  catch (RequestException $e) {
    // Failure. Either the video or playlist doesn't exist on Wistia.
    // Or there was an error with the request.
    watchdog_exception('field_wistia', $e);
  }

  return (array)$data;

}
