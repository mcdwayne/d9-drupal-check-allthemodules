<?php

namespace Drupal\siteimprove;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SiteimproveUtils.
 */
class SiteimproveUtils {

  use StringTranslationTrait;

  const TOKEN_REQUEST_URL = 'https://my2.siteimprove.com/auth/token?cms=nameAndVersionofCMSPlugin';

  /**
   * Current user var.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * ConfigFactory var.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * HTTP Client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $current_user, Client $http_client) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('http_client')
    );
  }

  /**
   * Return Siteimprove token.
   */
  public function requestToken() {

    try {
      // Request new token.
      $response = $this->httpClient->get(self::TOKEN_REQUEST_URL,
        ['headers' => ['Accept' => 'application/json']]);

      $data = (string) $response->getBody();
      if (!empty($data)) {
        $json = json_decode($data);
        if (!empty($json->token)) {
          return $json->token;
        }
        else {
          throw new \Exception();
        }
      }
      else {
        throw new \Exception();
      }
    }
    catch (\Exception $e) {
      watchdog_exception('siteimprove', $e, $this->t('There was an error requesting a new token.'));
    }

    return FALSE;
  }

  /**
   * Return Siteimprove js library.
   *
   * @return string
   *   Siteimprove js library.
   */
  public function getSiteimproveOverlayLibrary() {
    return 'siteimprove/siteimprove.overlay';
  }

  /**
   * Return siteimprove js library.
   */
  public function getSiteimproveLibrary() {
    return 'siteimprove/siteimprove';
  }

  /**
   * Return siteimprove js settings.
   *
   * @param string $url
   *   Url to input or recheck.
   * @param string $type
   *   Action: recheck_url|input_url.
   * @param bool $auto
   *   Automatic calling to the defined method.
   *
   * @return array
   *   JS settings.
   */
  public function getSiteimproveSettings($url, $type, $auto = TRUE) {
    return [
      'url' => $url,
      'auto' => $auto,
    ];
  }

  /**
   * Return siteimprove token.
   *
   * @return array|mixed|null
   *   Siteimprove Token.
   */
  public function getSiteimproveToken() {
    return $this->configFactory->get('siteimprove.settings')->get('token');
  }

  /**
   * Save URL in session.
   *
   * @param object $object
   *   Node or taxonomy term entity object.
   */
  public function setSessionUrl($object) {
    // Check if user has access.
    if ($this->currentUser->hasPermission('use siteimprove')) {
      // Save friendly url in SESSION.
      $_SESSION['siteimprove_url'][] = $object->toUrl('canonical', ['absolute' => TRUE])->toString();
    }
  }

}
