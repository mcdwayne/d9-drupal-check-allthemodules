<?php

namespace Drupal\drd_remote\Controller;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class DefaultController.
 *
 * @package Drupal\drd_remote\Controller
 */
class DefaultController extends ControllerBase {

  /**
   * @var ImmutableConfig
   */
  protected $config;

  /**
   * @inheritDoc
   */
  public function __construct() {
    $this->config = \Drupal::configFactory()->get('drd_remote.settings');
  }


  /**
   * Callback to download the DRD library if required,
   *
   * @param bool $force
   * @return string
   *    The URI of the available library which can be used to load the library.
   *
   * @throws \Exception
   */
  private function lib($force = FALSE) {
    $uri = 'temporary://drd.phar';
    if ($force || !file_exists($uri)) {
      // Send request
      try {
        $client = new Client(['base_uri' => 'http://cgit.drupalcode.org/drd/plain/build/drd.phar?h=' . $_SERVER['HTTP_X_DRD_VERSION']]);
        $response = $client->request('get');
      }
      catch (\Exception $ex) {
        throw new \Exception('Can not load DRD Library');
      }
      if ($response->getStatusCode() != 200) {
        throw new \Exception('DRD Library not available');
      }
      file_put_contents($uri, $response->getBody()->getContents());
    }
    return $uri;
  }

  public function get() {
    require $this->lib();
    return $this->deliver(\Drupal\drd\Remote\DrdActionBase::run(8, $this->config->get('debug_mode')));
  }

  public function getCryptMethods() {
    require $this->lib();
    return $this->deliver(base64_encode(serialize(\Drupal\drd\Crypt\DrdCryptBase::getMethods())));
  }

  /**
   * Callback that receives all relevant parameters from a monitoring DRD instance
   * base64 encoded and serialized in $values. This is called by a menu callback
   * or from drush.
   *
   * Security note: if called through a http request, this should only be done
   * over https as otherwise those parameters are travelling in plain text.
   *
   * @param string $values
   * @return TrustedRedirectResponse|string
   */
  public function setup($values) {
    $service = \Drupal::service('drd_remote.setup');
    $service->execute($values);
    if (!empty($values['redirect'])) {
      return new TrustedRedirectResponse($values['redirect']);
    }
    return $this->deliver('');
  }

  /**
   * Callback to deliver the result of the action in json format.
   *
   * @param $data
   * @return JsonResponse
   */
  function deliver($data) {
    return new JsonResponse($data, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
  }

}
