<?php

namespace Drupal\google_plus_login;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use League\OAuth2\Client\Provider\Google;

class GoogleProviderFactory {

  /**
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * GoogleProviderFactory constructor.
   *
   * @param ConfigFactoryInterface $configFactory
   * @param UrlGeneratorInterface $urlGenerator
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    UrlGeneratorInterface $urlGenerator
  ) {
    $this->configFactory = $configFactory;
    $this->urlGenerator = $urlGenerator;
  }

  /**
   * Factory method.
   *
   * @return Google
   */
  public function create() {
    $config = $this->configFactory->get('google_plus_login.settings');

    return new Google([
      'clientId' => $config->get('client_id'),
      'clientSecret' => $config->get('client_secret'),
      'redirectUri' => $this->urlGenerator->generateFromRoute(
        'google_plus_login.authenticate', [], ['absolute' => TRUE]
      ),
    ]);
  }

}
