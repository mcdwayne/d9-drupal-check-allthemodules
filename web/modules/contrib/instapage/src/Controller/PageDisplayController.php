<?php

namespace Drupal\instapage\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\instapage\Api;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller that handles Instapage paths.
 *
 * Class PageDisplayController.
 *
 * @package Drupal\instapage\Controller
 */
class PageDisplayController extends ControllerBase {

  private $httpClient;
  private $endpoint;
  private $request;

  /**
   * PageDisplayController constructor.
   *
   * @param \Drupal\instapage\Api $api
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   * @param \GuzzleHttp\Client $client
   */
  public function __construct(Api $api, Client $client, RequestStack $request) {
    $this->endpoint = $api::ENDPOINT;
    $this->request = $request->getCurrentRequest();
    $this->httpClient = $client;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('instapage.api'),
      $container->get('http_client'),
      $container->get('request_stack')
    );
  }

  /**
   * Return the page HTML.
   *
   * @param $instapage_id
   *
   * @return static
   */
  public function content($instapage_id) {
    $params = '?' . $this->request->getQueryString();
    $url = $this->endpoint . '/server/view-by-id/' . $instapage_id . $params;
    $data = $this->httpClient->get($url)->getBody()->getContents();
    $response = Response::create($data, 200);
    return $response;
  }

}
