<?php

/**
 * @file
 * Contains Drupal\ooyala\OoyalaManager.
 */

namespace Drupal\ooyala;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Asset\LibraryDiscoveryParser;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

use Drupal\ooyala\Form\OoyalaSettingsForm;
use Drupal\Core\Http\ClientFactory;

class OoyalaManager implements ContainerInjectionInterface, OoyalaManagerInterface {

  /**
   * API endpoint base
   */
  const API_BASE = 'https://api.ooyala.com';
  const CHUNK_SIZE = 200000;

  /**
   * ClientFactory
   */
  protected $clientFactory;

  /**
   * LibraryDiscoveryParser
   */
  protected $libraryParser;

  /**
   * Config
   */
  protected $config;

  /**
   * int Timeout for requests in seconds
   */
  protected $timeout = 10;

  /**
   * string The API key
   */
  protected $api_key;

  /**
   * string The secret key
   */
  protected $secret_key;

  /**
   * Constructor.
   *
   * @param PlanManager $planManager The plan manager
   */
  public function __construct(ConfigFactoryInterface $configFactory, ClientFactory $clientFactory, LibraryDiscoveryParser $libraryParser) {
    $this->config = $configFactory->get(OoyalaSettingsForm::CONFIG_KEY);
    $this->clientFactory = $clientFactory;
    $this->libraryParser = $libraryParser;

    $this->setCredentials($this->config->get('api_key'), $this->config->get('secret_key'));
  }

  /**
   * Return plugins info from this module's libraries definition.
   *
   * @param string $type ''|'optional'|'ad'
   * @return array
   */
  public function getPlugins($type = '') {
    if ($type) {
      $type = $type . '_';
    }

    if (!$this->libraries) {
      $this->libraries = $this->libraryParser->buildByExtension('ooyala');
    }

    return $this->libraries['ooyala_player'][$type . 'plugins'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client_factory'),
      $container->get('library.discovery.parser')
    );
  }

  /**
   * Set the API key and secret
   *
   * @param string $api_key
   * @param string $secret_key
   */
  public function setCredentials($api_key, $secret_key) {
    $this->api_key = $api_key;
    $this->secret_key = $secret_key;
  }

  /**
   * Test if we have credentials for logging in.
   *
   * @return bool
   */
  public function haveCredentials() {
    return !empty($this->api_key) && !empty($this->secret_key);
  }

  /**
   * Get an HTTP client for making a request
   */
  protected function getClient() {
    return $this->clientFactory->fromOptions([
      'base_uri' => self::API_BASE,
      'timeout' => $this->timeout
    ]);
  }

  /**
   * Test if the API is available by testing for a 400 or 200 response from
   * the Ooyala API endpoint.
   *
   * @return bool
   */
  public function apiAvailable() {
    // GuzzleHTTP might throw an exception if we get a 400 response,
    // which still verifies that the API is up
    try {
      $response = $this->getClient()->get('/v2/assets');

      return $response->getStatusCode() === 200;
    } catch(RequestException $e) {
      return $e->getResponse()->getStatusCode() === 400;
    }
  }

  /**
   * Test that the set API key and secret are valid.
   *
   * @return bool
   */
  public function validCredentials() {
    try {
      $response = $this->getClient()->get($this->generateURL('GET', '/v2/assets'));

      return $response->getStatusCode() === 200;
    } catch(RequestException $e) {
      // Just fall through to returning false
    }

    return false;
  }

  /**
   * Get available players.
   *
   * @return array
   */
  public function getPlayers() {
    try {
      $response = $this->getClient()->get($this->generateURL('GET', '/v2/players'));

      if ($response->getStatusCode() === 200) {
        $items = json_decode($response->getBody());
        $players = [];

        foreach ($items->items as $player) {
          $players[$player->id] = $player;
        }

        return $players;
      }
    } catch(RequestException $e) {
      // Just fall through to returning false
    }

    return false;
  }
  /**
   * Generates the url for the request.
   *
   * Ref: http://help.ooyala.com/video-platform/tasks/api_signing_requests.html#api_signing_requests
   *
   * @param string $HTTPMethod GET, POST, PUT, PATCH, DELETE
   * @param string $requestPath the path of the object to request
   * @param array $parameters array of paramaters [key => value]
   * @param string $requestBody body for the request
   * @return string theUrl generated
   */
  public function generateURL($HTTPMethod, $requestPath, $parameters = [], $requestBody = '') {
    $parameters['api_key'] = $this->api_key;
    $parameters['expires'] = $this->timeout + time();

    // Start with secret key, method, and path (no separator)
    $to_sign = $this->secret_key . $HTTPMethod . $requestPath;

    // Append all parameters, k=v, alphabetically by key
    $keys = array_keys($parameters);
    sort($keys);

    foreach ($keys as $key) {
      $to_sign .= $key . '=' . $parameters[$key];
    }

    // Append any body to the request
    $to_sign .= $requestBody;

    // Hash it
    $hash = hash('sha256', $to_sign, true);

    // Encode and trim it to 43 characters less any trailing =
    $sig = base64_encode($hash);
    $sig = substr($sig, 0, 43);
    $sig = rtrim($sig, '=');

    // And it's signed!
    $parameters['signature'] = $sig;

    return $requestPath . '?' . http_build_query($parameters);
  }

  /**
   * Initiate a request to the API.
   *
   * @return Response|null
   */
  public function request($method, $endpoint, $params = [], $body = '') {
    $body_json = $body === '' ? '' : json_encode($body);

    $api_request = $this->generateURL($method, $endpoint, $params, $body_json);

    $method_lc = strtolower($method);

    try {
      $options = [];

      if (!empty($body)) {
        $options['body'] = $body_json;
      }

      return $this->getClient()->$method_lc($api_request, $options);
    }
    catch(RequestException $e) {
      return $e->getResponse();
    }
  }

  /**
   * GET a request from the API.
   *
   * @return Response|null
   */
  public function get($endpoint, $params = []) {
    return $this->request('GET', $endpoint, $params);
  }

  /**
   * POST a request to the API.
   *
   * @return Response|null
   */
  public function post($endpoint, $body = []) {
    return $this->request('POST', $endpoint, [], $body);
  }

  /**
   * PUT a request to the API.
   *
   * @return Response|null
   */
  public function put($endpoint, $body = []) {
    return $this->request('PUT', $endpoint, [], $body);
  }

  /**
   * PATCH a request to the API.
   *
   * @return Response|null
   */
  public function patch($endpoint, $body = []) {
    return $this->request('PATCH', $endpoint, [], $body);
  }

  /**
   * DELETE a request to the API.
   *
   * @return Response|null
   */
  public function delete($endpoint) {
    return $this->request('DELETE', $endpoint);
  }

  /**
   * Search the API. Try three times because this API is unreliable.
   *
   * @return array|false
   */
  public function search($params, $try = 3) {
    $response = $this->get('/v2/assets', $params);

    if ($response->getStatusCode() === 200) {
      $body = json_decode((string) $response->getBody(), TRUE);

      if (!isset($body['items'])) {
        return false;
      }

      if (empty($body['items']) && $try > 0) {
        return $this->search($params, $try - 1);
      }

      return $body['items'];
    }

    return false;
  }

  /**
   * Search videos in the Backlot using the API
   */
  public function searchVideos(Request $request) {
    $query = $request->query->get('q');

    $query = str_replace('\'', '\\\'', $query);
    $params = [
      'include' => 'labels',
      'limit' => 50,
    ];

    if (!empty($query)) {
      $params['where'] =
        "description='$query' OR " .
        "name='$query' OR " .
        "embed_code='$query'";
    }

    $results = $this->search($params) ?: [];

    return new JsonResponse($results);
  }

  /**
   * Prepare a video upload in the Backlot using the API
   */
  public function uploadVideo(Request $request) {
    $file = $request->request->get('file');

    $meta = json_decode($file);

    if (!$meta || empty($meta->name)) {
      return new JsonResponse(false);
    }

    $create_response = $this->post('/v2/assets', [
      'asset_type' => 'video',
      'name' => $meta->name,
      'file_size' => $meta->size,
      'file_name' => $meta->file_name,
      'chunk_size' => self::CHUNK_SIZE,
    ]);

    // If we got an embed code back
    if (!empty($create_response) && $create_response->getStatusCode() === 200) {
      $create_body = json_decode($create_response->getBody());
    }
    else {
      return new JsonResponse(false);
    }

    // We got a new embed code, get upload URLs
    if (!empty($create_body->embed_code)) {
      $url_response = $this->get('/v2/assets/' . $create_body->embed_code . '/uploading_urls');
    }

    if (!empty($url_response) && $url_response->getStatusCode() === 200) {
      $urls = json_decode($url_response->getBody());
    }
    else {
      $urls = false;
    }

    return new JsonResponse([
      'item' => $create_body,
      'uploading_urls' => $urls,
    ]);
  }

  /**
   * Finish uploading a video
   */
  public function finishUpload(Request $request) {
    $item_json = $request->request->get('item');

    $item = json_decode($item_json);

    if (!$item || empty($item->embed_code)) {
      return new JsonResponse(false);
    }

    $status_response = $this->put('/v2/assets/' . $item->embed_code . '/upload_status', [
      'status' => 'uploaded',
    ]);

    // If we got an embed code back
    if ($status_response->getStatusCode() === 200) {
      $status_body = json_decode($status_response->getBody());
    }
    else {
      return new JsonResponse(false);
    }

    // Update the item with any new data
    $update_response = $this->patch('/v2/assets/' . $item->embed_code, $item);

    if ($update_response->getStatusCode() === 200) {
      return new JsonResponse($update_response->getBody());
    }

    return new JsonResponse(false);
  }

  /**
   * Cancel uploading a video: Delete the created asset.
   */
  public function cancelUpload(Request $request) {
    $embed_code = $request->query->get('embed_code');

    $delete_response = $this->delete('/v2/assets/' . $embed_code);

    if ($delete_response->getStatusCode() === 200) {
      return new JsonResponse(true);
    }

    return new JsonResponse(false);
  }

  /**
   * Return the custom CSS.
   */
  public function css(Request $request) {
    $custom_css = $this->config->get('custom_css');

    // Prefix the CSS rules with .ooyala-player
    $prefix = '.ooyala-player';

    $custom_css = preg_replace_callback('/([^{]+)(\{[^}]*}\s*)/', function($matches) use ($prefix) {
      // Add the prefix to each selector in a comma-separated group.
      // This WILL break if a selector has an embedded comma, like [href="foo,bar"]!
      return implode(',', array_map(function($selector) use ($prefix) {
        // Use selector '%' to apply to the prefix
        if (strpos($selector, '%') > -1) {
          return preg_replace('/%/', $prefix, $selector);
        }
        // Otherwise prepend the prefix to the given selector
        else {
          return $prefix . ' ' . $selector;
        }
      }, preg_split('/\s*,+\s*/', $matches[1]))) . $matches[2];
    }, $custom_css);

    // TODO: Making this explicitly cacheable. Adding cache tags. Invaliding cache tags
    // when the custom CSS config changes.
    return new Response(
      // Append the local ooyala_player.css with any configured custom CSS
      file_get_contents(dirname(__DIR__) . '/ooyala_player.css') . $custom_css,
      200,
      [ 'Content-Type' => 'text/css' ]
    );
  }

  /**
   * Render a player given a field instance.
   */
  public function render($video) {
    $inline = [];
    $item = json_decode($video->item);

    if (empty($item->embed_code)) {
      return;
    }

    if (!$this->libraries) {
      $this->libraries = $this->libraryParser->buildByExtension('ooyala');
    }

    $api_key = $this->config->get('api_key');

    // "Provider code" is the API key up to '.'
    $pcode = substr($api_key, 0, strpos($api_key, '.'));

    $container_id = 'ooyala-video-' . $item->embed_code;

    $classes = array('ooyala-player', 'ooyala-player-v4');

    // Add base video plugins
    $plugins = array_filter($this->config->get('plugins') ?: ['main_html5' => 'main_html5']);

    $libraries = ['ooyala/ooyala_player'];

    foreach ($plugins as $plugin) {
      $libraries[] = 'ooyala/ooyala_' . $plugin;
    }

    $global_addl_params = json_decode($this->config->get('additional_params'), TRUE);
    $video_addl_params = json_decode($video->additional_params, TRUE);

    $params = [
      'pcode' => $pcode,
      'playerBrandingId' => $this->config->get('player_id'),
      'autoplay' => !!$video->autoplay,
      'loop' => !!$video->loop,
      'skin' => [
        'config' => $this->libraries['ooyala_player']['base'] . 'skin-plugin/skin.json',
        'inline' => $video_addl_params + $global_addl_params,
      ],
      // Channels are not supported fully in the v3 player (Flash-only). Enable
      // them for the players that support it.
      'enableChannels' => TRUE,
      'wmode' => 'transparent',
    ];

    // Add ad plugins
    $ad_plugin = $this->config->get('ad_plugin');

    if (!empty($ad_plugin)) {
      // Special case for 'pulse' plugin:
      if ($ad_plugin === 'pulse') {
        // Add the pulse plugin script
        $ad_plugin = $this->config->get('pulse_plugin');

        // Don't use the default value for variable get as it may return an empty string
        if (empty($ad_plugin)) {
          $ad_plugin = OoyalaSettingsForm::DEFAULT_PULSE_PLUGIN;
        }

        // Append any JSON option overrides
        $pulse_options = $this->config->get('pulse_options');

        if (!empty($pulse_options) && ($pulse_options = json_decode($pulse_options, true))) {
          if (!isset($pulse_options['videoplaza-ads-manager'])) {
            $pulse_options = ['videoplaza-ads-manager' => $pulse_options];
          }

          $params += array_intersect_key($pulse_options, array_flip(['videoplaza-ads-manager']));
        }
      }

      $libraries[] = 'ooyala/ooyala_' . $ad_plugin;
    }

    $optional_plugins = $this->config->get('optional_plugins', []);

    foreach (array_keys($optional_plugins) as $plugin) {
      $libraries[] = 'ooyala/ooyala_' . $plugin;
    }

    $element['#attached']['library'] = $libraries;

    $params_json = json_encode([
      $container_id,
      $item->embed_code,
      $params,
    ]);

    $noscript_text = t('Please enable Javascript to watch this video');

    $classes = implode(' ', $classes);

    $element['container'] = [
      '#markup' => "<div class=\"$classes\"><div id=\"$container_id\"></div></div>"
    ];

    $script = <<<"XXX"
  var ooyalaplayers = ooyalaplayers || [];

  OO.ready(function() {
    var op = typeof window.ooyalaParameters === 'function' ? window.ooyalaParameters : function(params) { return params; },
        params = op($params_json)
    ;

    params[2].onCreate = Drupal.ooyala.onCreate;
    OO.Player.create.apply(OO.Player, params);
  });
XXX;

    $element['script'] = [
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#attributes' => [ 'type' => 'text/javascript' ],
      '#value' => $script,
    ];

    $element['noscript'] = [
      '#type' => 'html_tag',
      '#tag' => 'noscript',
      '#value' => $noscript_text,
    ];

    return $element;
  }

  /**
   * Define plugins derived from plugins, ad_plugins, and optional_plugins
   * keys in the module ooyala_player library definition as their own libraries
   * prepended with a base URL. This allows us to easily add plugins via the
   * module's .libraries.yml file without having to modify code.
   */
  public function alterLibraries(&$libraries) {
    $library = &$libraries['ooyala_player'];
    $base = $library['base'];

    $suffix = '.js';

    $library['header'] = true;
    $library['js'][$base . 'core' . $suffix] = ['external' => true];
    $library['js'][$base . 'skin-plugin/html5-skin' . $suffix] = ['external' => true];

    $all_plugins = [
      'video-plugin/' => $library['plugins'],
      'ad-plugin/' => $library['ad_plugins'],
      'other-plugin/' => $library['optional_plugins'],
    ];

    foreach ($all_plugins as $prefix => $plugins) {
      foreach ($plugins as $plugin => $desc) {
        if (!$plugin) {
          continue;
        }

        // Only one JS file per plugin
        $libraries['ooyala_' . $plugin] = [
          'header' => true,
          'js' => [($base . $prefix . $plugin . $suffix) => ['external' => true]],
        ];
      }
    }

    // Load external CSS, then local overrides
    $library['css']['theme'][$base . 'skin-plugin/html5-skin.min.css'] = ['type' => 'external'];
    $library['css']['theme']['/ooyala.css'] = [];
  }

}

