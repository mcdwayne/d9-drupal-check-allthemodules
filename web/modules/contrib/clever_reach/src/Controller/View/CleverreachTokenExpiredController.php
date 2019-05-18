<?php

namespace Drupal\clever_reach\Controller\View;

use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\BusinessLogic\Interfaces\Proxy;

/**
 * Class CleverreachTokenExpiredController.
 *
 * @package Drupal\clever_reach\Controller\View
 */
class CleverreachTokenExpiredController extends CleverreachResolveStateController {

  const CURRENT_STATE_CODE = 'tokenexpired';
  const TEMPLATE = 'cleverreach_token_expired';

  /**
   * Instance of \CleverReach\BusinessLogic\Interfaces\Proxy class.
   *
   * @var \CleverReach\BusinessLogic\Interfaces\Proxy
   */
  private $proxy;

  /**
   * Callback for the cleverreach.cleverreach.welcome route.
   *
   * @return array
   *   Template variables.
   */
  public function content() {
    $this->dispatch();
    $userInfo = $this->getConfigService()->getUserInfo();
    $clientId = $userInfo['id'];
    $integrationName = $this->getConfigService()->getIntegrationName();
    $translatableHello = t('icon_hello');

    return [
      '#urls' => [
        'logo_url' => $this->getThemePath(
          'images/' . $translatableHello . '.png'
        ),
        'auth_url' => $this->getAuthUrl(),
        'check_status_url' => $this->getControllerUrl('auth.check.status'),
        'wakeup_url' => $this->getControllerUrl('wakeup'),
      ],
      '#translations' => [
        'error_title' => t('Error: token expired'),
        'error_message' => sprintf(
          t('It seems that it was a long time since any event happened 
          inside your %s and CleverReach® access token expired. In order to 
          let system synchronize data for you, you\'ll have to authenticate 
          again with CleverReach® ID %s . If you want to use different 
          CleverReach® account, you need to reinstall this extension .'),
          $integrationName,
          $clientId),
        'authenticate' => t('Authenticate now'),
      ],
      '#theme' => self::TEMPLATE,
      '#attached' => [
        'library' => [
          'clever_reach/cleverreach-token-expired-view',
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
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    /** @var \CleverReach\BusinessLogic\Proxy $proxy */
    $proxy = $this->getProxyService();

    return $proxy->getAuthUrl(
      $this->getControllerUrl(
        'callback',
        [
          'refresh' => TRUE,
        ]
      ),
      '',
      ['lang' => $language]
    );
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
