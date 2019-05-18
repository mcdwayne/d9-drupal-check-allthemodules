<?php

namespace Drupal\google_plus_login\EventListener;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\google_plus_login\Controller\GooglePlusLoginController;
use Symfony\Component\Routing\RouteCollection;

class RouteSubscriber extends RouteSubscriberBase {

  /**
   * @var ImmutableConfig
   */
  protected $config;

  /**
   * RouteSubscriber constructor.
   *
   * @param ConfigFactoryInterface $configFactory
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->config = $configFactory->get('google_plus_login.settings');
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if (!$this->config->get('google_login_override')) {
      return; // bail, only alter the route if this option is true.
    }

    $loginRoute = clone $collection->get('user.login');
    $loginRoute->setPath('/user/site-login');

    // Register the new Drupal login route.
    $collection->add('user.drupal_login', $loginRoute);

    // Alter the route /user/login to return the Google OAuth controller.
    $collection
      ->get('user.login')
      ->setDefaults([
        '_controller' => GooglePlusLoginController::class . '::loginAction',
        '_title' => 'Log in',
      ]);
  }

}
