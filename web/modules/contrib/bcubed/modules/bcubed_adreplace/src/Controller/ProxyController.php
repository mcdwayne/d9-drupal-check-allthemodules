<?php

namespace Drupal\bcubed_adreplace\Controller;

use Drupal\bcubed\StringGenerator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ProxyController.
 *
 * @package Drupal\bcubed_adreplace\Controller
 */
class ProxyController implements ContainerInjectionInterface {

  /**
   * The generated strings.
   *
   * @var generatedStrings
   *   Generated Strings.
   */
  protected $generatedStrings;

  /**
   * Constructs a new ProxyController object.
   *
   * @param \Drupal\bcubed\StringGenerator $string_generator
   *   String Generator object.
   */
  public function __construct(StringGenerator $string_generator) {
    $this->generatedStrings = $string_generator->getStrings('bcubed_adreplace');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bcubed.string_generator')
    );
  }

  /**
   * Fetch replacement element (ad tag) from revive.
   */
  public function replacementElement(Request $request) {
    $zones = $request->query->get('ids');
    // Removed from below: 'r' => floor(rand()*99999999),
    $query = http_build_query([
      'zones' => $zones,
      'source' => '',
      'charset' => 'UTF-8',
      'loc' => $request->headers->get('referer'),
      'logimpression' => '1',
    ]);
    $url = 'https://bcubed.adtumbler.com/www/delivery/spcjson.php?' . $query;

    $data = $this->makeRequest($request, $url);

    $alldata = json_decode($data['body']);
    $ads = [];
    foreach ($alldata as $ad) {
      // Replace every url to route through the proxy, except for click tracking on outbound links
      // $body = preg_replace('/(?!.*(ck.php))https*:\/\/bcubed.adtumbler.com\/www/', '/fJAe34v5', $data['body']);.
      if (isset($ad->url) && isset($ad->bannerContent)) {
        $body = '<a class="' . $this->generatedStrings['link_class'] . '" data-' . strtolower($this->generatedStrings['banner_identifier']) . '="' . $ad->bannerid . '" href="' . $ad->url . '" target="_blank">';
        $body .= '<img src="' . preg_replace('/https*:\/\/bcubed.adtumbler.com\/www/', '/' . $this->generatedStrings['main_proxy'], $ad->bannerContent) . '" />';
        $body .= '</a>';
        $session = $request->getSession();
        $session->set('bcubed_adreplace_clickurl_' . $ad->bannerid, $ad->clickUrl);
      }
      else {
        $body = '';
      }
      $ads[] = $body;
    }

    $output = json_encode($ads);

    if (!empty($data['cookies'])) {
      $response = Response::create($output, $data['code'], ['Content-Type' => 'application/json']);
      foreach ($data['cookies'] as $cookie) {
        $response->headers->set('Set-Cookie', $cookie, FALSE);
      }
    }
    else {
      $response = Response::create($output, $data['code'], ['Content-Type' => 'application/json']);
    }

    return $response;
  }

  /**
   * Process requests.
   */
  public function request($locator, $resource, Request $request) {
    $url = 'https://bcubed.adtumbler.com/www/' . $locator . '/' . $resource . '?' . $request->getQueryString();

    $data = $this->makeRequest($request, $url);

    if (!empty($data['cookies'])) {
      $response = Response::create($data['body'], $data['code'], ['Content-Type' => $data['type']]);
      foreach ($data['cookies'] as $cookie) {
        $response->headers->set('Set-Cookie', $cookie, FALSE);
      }
    }
    else {
      $response = Response::create($data['body'], 200, ['Content-Type' => $data['type']]);
    }
    return $response;
  }

  /**
   * Track clicks.
   */
  public function trackClick($banner, Request $request) {
    $session = $request->getSession();

    $url = $session->get('bcubed_adreplace_clickurl_' . $banner);

    $data = $this->makeRequest($request, $url, FALSE);

    if ($data['code'] == 302) {
      $data['code'] = 200;
    }

    if (!empty($data['cookies'])) {
      $response = Response::create($data['body'], $data['code'], ['Content-Type' => $data['type']]);
      foreach ($data['cookies'] as $cookie) {
        $response->headers->set('Set-Cookie', $cookie, FALSE);
      }
    }
    else {
      $response = Response::create($data['body'], $data['code'], ['Content-Type' => $data['type']]);
    }

    return $response;
  }

  /**
   * Internal function to make requests.
   */
  private function makeRequest(Request $request, $url, $return_body = TRUE) {
    $ch = curl_init($url);

    // Set localhost as a trusted proxy.
    $request->setTrustedProxies(['127.0.0.1']);

    $header = [
      'User-Agent: ' . $request->headers->get('user-agent'),
      'Accept: image/*,*/*;q=0.8',
      'Accept-Language: en-us,en;q=0.5',
      'Accept-Encoding: gzip,deflate',
      'Accept-Charset: utf-8,*;q=0.7',
      'Keep-Alive: 115',
      'Connection: keep-alive',
      'X-Forwarded-For: ' . $request->getClientIp(),
      'Referer: ' . $request->headers->get('referer'),
    ];

    // Grab cookies.
    $cookiestring = '';
    $cookies = $request->cookies->all();

    foreach ($cookies as $name => $value) {
      if (strpos($name, $this->generatedStrings['cookie_prefix']) === 0 && strpos($value, "\n") === FALSE) {
        if (is_array($value)) {
          $key = array_keys($value)[0];
          $name .= '[' . $key . ']';
          $value = $value[$key];
        }
        $cookiestring .= ' ' . substr($name, strlen($this->generatedStrings['cookie_prefix'])) . '=' . $value . ';';
      }
    }

    if (!empty($cookiestring)) {
      curl_setopt($ch, CURLOPT_COOKIE, substr($cookiestring, 1));
    }

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $return_body);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");

    $result = curl_exec($ch);

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header = substr($result, 0, $header_size);

    $body = $return_body ? substr($result, $header_size) : 'Success';

    // Pass through cookies.
    preg_match_all('/[\n\r].*Set-Cookie:\s*([^=]*)([^\n\r]*)/i', $header, $matches);
    $cookies = [];
    for ($i = 0, $size = count($matches[1]); $i < $size; ++$i) {
      $cookies[] = $this->generatedStrings['cookie_prefix'] . $matches[1][$i] . $matches[2][$i];
    }

    preg_match('/^Content-Type:\s*([^\n\r]*)/im', $header, $content_type);

    if (empty($content_type)) {
      $content_type = ['text/html'];
    }

    return [
      'body' => $body,
      'code' => $response_code,
      'cookies' => $cookies,
      'type' => $content_type[1],
    ];
  }

}
