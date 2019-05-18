<?php

namespace Drupal\cleverreach\Controller\View;

use CleverReach\BusinessLogic\Interfaces\Proxy;
use CleverReach\Infrastructure\ServiceRegister;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Welcome View Controller.
 *
 * @see template file cleverreach-welcome.html.twig
 */
class CleverreachWelcomeController extends CleverreachResolveStateController {
  const CURRENT_STATE_CODE = 'welcome';
  const TEMPLATE = 'cleverreach_welcome';

  /**
   * Instance of \CleverReach\BusinessLogic\Interfaces\Proxy class.
   *
   * @var \CleverReach\BusinessLogic\Interfaces\Proxy
   */
  private $proxy;
  /**
   * Instance of \Drupal\Core\Session\AccountProxy class.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  private $currentUser;
  /**
   * Instance of \Drupal\Core\Config\ConfigFactoryInterface class.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * CleverreachWelcomeController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config Factory.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   Account proxy.
   */
  public function __construct(ConfigFactoryInterface $configFactory, AccountProxy $currentUser) {
    $this->currentUser = $currentUser;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory'),
        $container->get('current_user')
    );
  }

  /**
   * Callback for the cleverreach.cleverreach.welcome route.
   *
   * @return array
   *   Template variables.
   */
  public function content() {
    $this->dispatch();

    return [
      '#urls' => [
        'logo_url' => $this->getThemePath('images/icon_hello.png'),
        'auth_url' => $this->getAuthUrl(),
        'check_status_url' => $this->getControllerUrl('auth.check.status'),
        'wakeup_url' => $this->getControllerUrl('wakeup'),
      ],
      '#theme' => self::TEMPLATE,
      '#attached' => [
        'library' => [
          'cleverreach/cleverreach-welcome-view',
        ],
      ],
    ];
  }

  /**
   * Gets auth URL with all register data.
   *
   * @return string
   *   Base 64 encoded array of new user parameters
   *   retrieved from configuration.
   */
  private function getAuthUrl() {
    /** @var \CleverReach\BusinessLogic\Proxy $proxy */
    $proxy = $this->getProxyService();
    $registerData = base64_encode(json_encode($this->getRegisterData()));

    return $proxy->getAuthUrl($this->getControllerUrl('callback'), $registerData);
  }

  /**
   * Gets admin user registration data for CleverReach.
   *
   * @return array
   *   List of available parameters for current user.
   */
  private function getRegisterData() {
    $username = $this->currentUser->getUsername();
    $siteName = $this->getConfigService()->getSiteName();
    $email = $this->configFactory->get('system.site')->get('mail');

    return [
      'email' => $email,
      'company' => $siteName,
      'firstname' => $username,
      'lastname' => '',
      'gender' => '',
      'street' => '',
      'zip' => '',
      'city' => '',
      'country' => '',
      'phone' => '',
    ];
  }

  /**
   * Gets CleverReach Proxy class.
   *
   * @return \CleverReach\BusinessLogic\Interfaces\Proxy
   *   CleverReach Proxy.
   *
   * @throws \InvalidArgumentException
   */
  private function getProxyService() {
    if (NULL === $this->proxy) {
      $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
    }

    return $this->proxy;
  }

}
