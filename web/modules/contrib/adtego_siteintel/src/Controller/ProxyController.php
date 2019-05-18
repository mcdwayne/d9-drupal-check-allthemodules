<?php

namespace Drupal\adtego_siteintel\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ProxyController.
 *
 * @package Drupal\adtego_siteintel\Controller
 */
class ProxyController {

  /**
   * Process requests.
   */
  public function request($method, Request $request) {

    $ch = curl_init('https://admin.adtego.com/' . $method . '?' . $request->getQueryString());

    // Set localhost as a trusted proxy.
    $request->setTrustedProxies(array('127.0.0.1'));

    $header = array(
      'User-Agent: ' . $request->headers->get('user-agent'),
      'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
      'Accept-Language: en-us,en;q=0.5',
      'Accept-Encoding: gzip,deflate',
      'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
      'Keep-Alive: 115',
      'Connection: keep-alive',
      'X-Forwarded-For: ' . $request->getClientIp(),
      'X-Requested-With: XMLHttpRequest',
    );

    $timeout = 5;
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");

    // Check for cookie.
    if ($request->cookies->has('atid')) {
      curl_setopt($ch, CURLOPT_COOKIE, 'atid=' . $request->cookies->get('atid'));
    }

    $result = curl_exec($ch);

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header = substr($result, 0, $header_size);

    // Pass through 1st cookie.
    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $header, $matches);
    $cookie = reset($matches[1]);

    $body = substr($result, $header_size);

    curl_close($ch);

    if ($cookie) {
      $response = Response::create($body, $response_code, ['Set-Cookie' => $cookie, 'Content-Type' => 'text/javascript; charset=UTF-8']);
    }
    else {
      $response = Response::create($body, $response_code, ['Content-Type' => 'text/javascript; charset=UTF-8']);
    }
    return $response;
  }

}
